<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\StockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\StockMovementServiceInterface;
use Modules\Inventory\Domain\Entities\StockLevel;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\Inventory\Domain\Events\StockMovementCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\StockLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

class StockMovementService implements StockMovementServiceInterface
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $movementRepository,
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
        private readonly StockLevelServiceInterface $stockLevelService,
    ) {}

    public function getMovement(string $tenantId, string $id): StockMovement
    {
        $movement = $this->movementRepository->findById($tenantId, $id);

        if ($movement === null) {
            throw new NotFoundException('StockMovement', $id);
        }

        return $movement;
    }

    public function getMovementsByProduct(string $tenantId, string $productId): array
    {
        return $this->movementRepository->findByProduct($tenantId, $productId);
    }

    public function recordMovement(string $tenantId, array $data): StockMovement
    {
        return DB::transaction(function () use ($tenantId, $data): StockMovement {
            $now = now();
            $movement = new StockMovement(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                productId: $data['product_id'],
                variantId: $data['variant_id'] ?? null,
                warehouseId: $data['warehouse_id'],
                locationId: $data['location_id'] ?? null,
                type: $data['type'],
                quantity: (float) $data['quantity'],
                batchNumber: $data['batch_number'] ?? null,
                lotNumber: $data['lot_number'] ?? null,
                serialNumber: $data['serial_number'] ?? null,
                referenceType: $data['reference_type'] ?? null,
                referenceId: $data['reference_id'] ?? null,
                notes: $data['notes'] ?? null,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->movementRepository->save($movement);

            $this->applyStockAdjustment($tenantId, $movement);

            Event::dispatch(new StockMovementCreated($movement));

            return $movement;
        });
    }

    private function applyStockAdjustment(string $tenantId, StockMovement $movement): void
    {
        $delta = match ($movement->type) {
            'receive', 'return' => $movement->quantity,
            'issue'             => -$movement->quantity,
            'adjust'            => $movement->quantity,
            default             => null,
        };

        if ($delta === null) {
            return;
        }

        $levels = $this->stockLevelRepository->findByProduct(
            $tenantId,
            $movement->productId,
            $movement->variantId,
        );

        $matchingLevel = null;
        foreach ($levels as $level) {
            if ($level->warehouseId === $movement->warehouseId
                && $level->locationId === $movement->locationId
            ) {
                $matchingLevel = $level;
                break;
            }
        }

        if ($matchingLevel !== null) {
            $this->stockLevelService->adjustQuantity($tenantId, $matchingLevel->id, $delta);
        } else {
            $initialQty = $delta > 0.0 ? $delta : 0.0;
            $now = now();
            $newLevel = new StockLevel(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                productId: $movement->productId,
                variantId: $movement->variantId,
                warehouseId: $movement->warehouseId,
                locationId: $movement->locationId,
                batchNumber: $movement->batchNumber,
                lotNumber: $movement->lotNumber,
                serialNumber: $movement->serialNumber,
                quantity: $initialQty,
                reservedQuantity: 0.0,
                expiryDate: null,
                createdAt: $now,
                updatedAt: $now,
            );
            $this->stockLevelRepository->save($newLevel);
        }
    }
}
