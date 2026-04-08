<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;
use Modules\Returns\Application\Contracts\ProcessReturnInventoryAdjustmentServiceInterface;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnInventoryAdjusted;
use Modules\Returns\Domain\Exceptions\StockReturnNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\Events\StockMovementConfirmed;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

/**
 * ProcessReturnInventoryAdjustmentService
 *
 * Enterprise-grade cross-module service that orchestrates inventory impact
 * when a stock return is completed. For each approved return line it:
 *
 *  1. Creates a confirmed StockMovement (return_in / adjustment) for full
 *     audit traceability across all disposition types.
 *  2. For "restock" disposition lines:
 *     - Increments the InventoryLevel (or creates it if none exists yet).
 *     - Creates a new InventoryValuationLayer at the return unit cost,
 *       honouring the tenant's configured valuation method (FIFO/LIFO/AVCO/…).
 *  3. Fires StockMovementConfirmed and StockReturnInventoryAdjusted domain
 *     events for downstream listeners and audit compliance.
 *
 * Disposition rules:
 *   - restock       → inventory level ↑, new valuation layer created
 *   - scrap         → adjustment StockMovement, no inventory increase
 *   - vendor_return → return_out StockMovement, no local inventory increase
 *   - quarantine    → return_in StockMovement, no level increase (hold)
 */
class ProcessReturnInventoryAdjustmentService extends BaseService implements ProcessReturnInventoryAdjustmentServiceInterface
{
    public function __construct(
        private readonly StockReturnRepositoryInterface $returnRepository,
        private readonly StockReturnLineRepositoryInterface $lineRepository,
        private readonly StockMovementRepositoryInterface $movementRepository,
        private readonly InventoryLevelRepositoryInterface $levelRepository,
        private readonly InventoryValuationLayerRepositoryInterface $valuationLayerRepository,
        private readonly InventorySettingRepositoryInterface $settingRepository,
    ) {
        parent::__construct($returnRepository);
    }

    protected function handle(array $data): StockReturn
    {
        $returnId = (int) $data['id'];
        $return   = $this->returnRepository->find($returnId);

        if (! $return) {
            throw new StockReturnNotFoundException($returnId);
        }

        // Resolve tenant-level valuation method (defaults to FIFO when not configured).
        $setting         = $this->settingRepository->findByTenant($return->getTenantId());
        $valuationMethod = $setting ? $setting->getValuationMethod() : 'fifo';

        // Process every approved return line.
        $lines = $this->lineRepository->findByReturn($return->getTenantId(), $returnId);

        foreach ($lines as $line) {
            $approvedQty = $line->getQuantityApproved() ?? $line->getQuantityRequested();
            if ($approvedQty <= 0.0) {
                continue;
            }

            $disposition = $line->getDisposition();

            // ── 1. Create a confirmed StockMovement ──────────────────────
            $movementType = match ($disposition) {
                'scrap'         => 'adjustment',
                'vendor_return' => 'return_out',
                default         => 'return_in',   // restock, quarantine
            };

            $refNum = 'MOV-' . $return->getReferenceNumber() . '-' . $line->getProductId();

            $movement = new StockMovement(
                tenantId:      $return->getTenantId(),
                referenceNumber: $refNum,
                movementType:  $movementType,
                productId:     $line->getProductId(),
                quantity:      $approvedQty,
                variationId:   $line->getVariationId(),
                fromLocationId: null,
                toLocationId:  $disposition === 'restock' ? $return->getRestockLocationId() : null,
                batchId:       $line->getBatchId(),
                serialNumberId: $line->getSerialNumberId(),
                uomId:         $line->getUomId(),
                unitCost:      $line->getUnitCost(),
                currency:      $return->getCurrency(),
                referenceType: 'stock_return',
                referenceId:   $returnId,
                notes:         'Auto-generated from return ' . $return->getReferenceNumber(),
            );
            $movement->confirm();
            $savedMovement = $this->movementRepository->save($movement);
            $this->addEvent(new StockMovementConfirmed($savedMovement));

            // ── 2. For "restock" disposition: adjust inventory ────────────
            if ($disposition === 'restock') {
                $locationId = $return->getRestockLocationId();

                // Adjust or create InventoryLevel.
                $level = $this->levelRepository->findByProductAndLocation(
                    $return->getTenantId(),
                    $line->getProductId(),
                    $locationId,
                    $line->getBatchId()
                );

                if ($level !== null) {
                    $level->addStock($approvedQty);
                } else {
                    $level = new InventoryLevel(
                        tenantId:    $return->getTenantId(),
                        productId:   $line->getProductId(),
                        variationId: $line->getVariationId(),
                        locationId:  $locationId,
                        batchId:     $line->getBatchId(),
                        uomId:       $line->getUomId(),
                        qtyOnHand:   $approvedQty,
                        qtyReserved: 0.0,
                        qtyOnOrder:  0.0,
                    );
                }

                $this->levelRepository->save($level);

                // Create a new InventoryValuationLayer at the return unit cost.
                // This correctly accounts for FIFO/LIFO/AVCO layering:
                //   - FIFO/LIFO  → new layer is inserted and will be ordered by date
                //   - AVCO       → weighted average is recalculated by consumers
                //   - Standard   → layer carries the standard cost
                $unitCost = $line->getUnitCost() ?? 0.0;

                $layer = new InventoryValuationLayer(
                    tenantId:        $return->getTenantId(),
                    productId:       $line->getProductId(),
                    layerDate:       new \DateTimeImmutable,
                    qtyIn:           $approvedQty,
                    unitCost:        $unitCost,
                    valuationMethod: $valuationMethod,
                    variationId:     $line->getVariationId(),
                    batchId:         $line->getBatchId(),
                    locationId:      $locationId,
                    qtyRemaining:    $approvedQty,
                    currency:        $return->getCurrency(),
                    referenceType:   'stock_return',
                    referenceId:     $returnId,
                );

                $this->valuationLayerRepository->save($layer);
            }
        }

        $this->addEvent(new StockReturnInventoryAdjusted($return));

        return $return;
    }
}
