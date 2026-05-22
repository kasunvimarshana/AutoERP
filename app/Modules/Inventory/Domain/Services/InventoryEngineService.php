<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Domain\Contracts\AccountingIntegrationContract;
use Modules\Inventory\Domain\Contracts\InventoryReadRepositoryContract;
use Modules\Inventory\Domain\Contracts\InventoryWriteRepositoryContract;
use Modules\Inventory\Domain\Contracts\UomConversionServiceContract;
use Modules\Inventory\Domain\Enums\StockMovementDirection;
use Modules\Inventory\Domain\Events\InventoryMovementValuated;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use Modules\Inventory\Domain\Support\Decimal;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovement;

class InventoryEngineService
{
    public function __construct(
        private readonly InventoryConfigService $configService,
        private readonly StrategyResolverService $strategyResolver,
        private readonly InventoryReadRepositoryContract $readRepository,
        private readonly InventoryWriteRepositoryContract $writeRepository,
        private readonly UomConversionServiceContract $uomConversionService,
        private readonly AccountingIntegrationContract $accountingIntegration,
    ) {
    }

    public function post(MovementLineDTO $line): StockMovement
    {
        if (!$this->configService->valuationEnabled($line->tenantId, $line->organizationUnitId, $line->warehouseId)) {
            throw new InsufficientStockException('Inventory valuation feature is disabled for this scope.');
        }

        $baseUomId = $this->readRepository->findItemBaseUomId($line->itemId);
        $baseQuantity = $this->uomConversionService->toBaseQuantity(
            tenantId: $line->tenantId,
            itemId: $line->itemId,
            fromUomId: $line->uomId,
            toUomId: $baseUomId,
            quantity: $line->quantity,
        );
        $normalizedLine = $line->withQuantityAndUom($baseQuantity, $baseUomId);

        return $this->writeRepository->transaction(function () use ($normalizedLine): StockMovement {
            $valuationMethod = $this->readRepository->resolveValuationMethod($normalizedLine);
            $valuationStrategy = $this->strategyResolver->resolveValuation($valuationMethod);

            if ($normalizedLine->direction === StockMovementDirection::In) {
                $inboundCost = $valuationStrategy->resolveInboundUnitCost(
                    $normalizedLine,
                    $this->readRepository->findStandardUnitCost($normalizedLine)
                );

                $movement = $this->writeRepository->createMovement(
                    line: $normalizedLine,
                    quantityIn: $normalizedLine->quantity,
                    quantityOut: 0.0,
                    unitCost: $inboundCost,
                    totalCost: Decimal::mul($normalizedLine->quantity, $inboundCost),
                );

                $this->writeRepository->applyInbound($normalizedLine, $inboundCost);
                $this->emitValuationEvent($movement, $normalizedLine, $inboundCost);

                return $movement;
            }

            if (!$this->configService->allocationEnabled($normalizedLine->tenantId, $normalizedLine->organizationUnitId, $normalizedLine->warehouseId)) {
                throw new InsufficientStockException('Inventory allocation feature is disabled for this scope.');
            }

            $available = $this->readRepository->findAvailableQuantityForUpdate($normalizedLine);
            if ($available < $normalizedLine->quantity) {
                throw InsufficientStockException::forRequested($normalizedLine->quantity, $available);
            }

            $allocationMethod = $this->readRepository->resolveAllocationMethod($normalizedLine);
            $allocationStrategy = $this->strategyResolver->resolveAllocation($allocationMethod);

            $layers = $this->readRepository->fetchOpenLayersForUpdate($normalizedLine);
            $allocation = $allocationStrategy->allocate($layers, $normalizedLine);

            if ($allocation->remainingQuantity() > 0.0) {
                throw InsufficientStockException::forRequested($normalizedLine->quantity, $allocation->allocatedQuantity);
            }

            $fallbackCost = match ($valuationMethod->value) {
                'WEIGHTED_AVERAGE', 'MOVING_AVERAGE' => $this->readRepository->findCurrentWeightedUnitCost($normalizedLine),
                'STANDARD_COST' => $this->readRepository->findStandardUnitCost($normalizedLine),
                'REPLACEMENT_COST' => $this->readRepository->findReplacementUnitCost($normalizedLine),
                default => null,
            };

            if ($fallbackCost !== null) {
                $normalizedLine = $normalizedLine->withQuantityAndUom($normalizedLine->quantity, $normalizedLine->uomId);
                $metadata = $normalizedLine->metadata;
                if ($valuationMethod->value === 'STANDARD_COST') {
                    $metadata['standard_cost'] = $fallbackCost;
                }
                if ($valuationMethod->value === 'REPLACEMENT_COST') {
                    $metadata['replacement_unit_cost'] = $fallbackCost;
                }
                $normalizedLine = new MovementLineDTO(
                    tenantId: $normalizedLine->tenantId,
                    organizationUnitId: $normalizedLine->organizationUnitId,
                    itemId: $normalizedLine->itemId,
                    variantId: $normalizedLine->variantId,
                    warehouseId: $normalizedLine->warehouseId,
                    locationId: $normalizedLine->locationId,
                    batchId: $normalizedLine->batchId,
                    serialId: $normalizedLine->serialId,
                    uomId: $normalizedLine->uomId,
                    quantity: $normalizedLine->quantity,
                    direction: $normalizedLine->direction,
                    txnType: $normalizedLine->txnType,
                    providedUnitCost: $normalizedLine->providedUnitCost,
                    performedBy: $normalizedLine->performedBy,
                    referenceType: $normalizedLine->referenceType,
                    referenceId: $normalizedLine->referenceId,
                    notes: $normalizedLine->notes,
                    currencyId: $normalizedLine->currencyId,
                    exchangeRate: $normalizedLine->exchangeRate,
                    metadata: $metadata,
                );
            }

            $unitCost = $valuationStrategy->resolveOutboundUnitCost($normalizedLine, $allocation);
            $movement = $this->writeRepository->createMovement(
                line: $normalizedLine,
                quantityIn: 0.0,
                quantityOut: $normalizedLine->quantity,
                unitCost: $unitCost,
                totalCost: $allocation->totalCost(),
                allocation: $allocation,
            );

            $this->writeRepository->applyOutbound($normalizedLine, $allocation);
            $this->emitValuationEvent($movement, $normalizedLine, $unitCost);

            return $movement;
        });
    }

    private function emitValuationEvent(StockMovement $movement, MovementLineDTO $line, float $unitCost): void
    {
        $event = new InventoryMovementValuated(
            movementId: (int) $movement->id,
            tenantId: $line->tenantId,
            organizationUnitId: $line->organizationUnitId,
            itemId: $line->itemId,
            variantId: $line->variantId,
            direction: $line->direction->value,
            txnType: $line->txnType,
            quantity: $line->quantity,
            unitCost: $unitCost,
            totalCost: (float) ($movement->total_cost ?? Decimal::mul($line->quantity, $unitCost)),
            metadata: $line->metadata,
        );

        Event::dispatch($event);
        $this->accountingIntegration->post($event);
    }
}
