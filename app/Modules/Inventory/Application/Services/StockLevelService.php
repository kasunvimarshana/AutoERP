<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\StockLevelServiceInterface;
use Modules\Inventory\Domain\Entities\StockLevel;
use Modules\Inventory\Domain\Events\StockLevelAdjusted;
use Modules\Inventory\Domain\RepositoryInterfaces\StockLevelRepositoryInterface;

class StockLevelService implements StockLevelServiceInterface
{
    public function __construct(
        private readonly StockLevelRepositoryInterface $stockLevelRepository,
    ) {}

    public function getStockLevel(string $tenantId, string $id): StockLevel
    {
        $level = $this->stockLevelRepository->findById($tenantId, $id);

        if ($level === null) {
            throw new NotFoundException('StockLevel', $id);
        }

        return $level;
    }

    public function getStockByProduct(string $tenantId, string $productId, ?string $variantId = null): array
    {
        return $this->stockLevelRepository->findByProduct($tenantId, $productId, $variantId);
    }

    public function getStockByWarehouse(string $tenantId, string $warehouseId): array
    {
        return $this->stockLevelRepository->findByWarehouse($tenantId, $warehouseId);
    }

    public function createStockLevel(string $tenantId, array $data): StockLevel
    {
        return DB::transaction(function () use ($tenantId, $data): StockLevel {
            $now = now();
            $level = new StockLevel(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                productId: $data['product_id'],
                variantId: $data['variant_id'] ?? null,
                warehouseId: $data['warehouse_id'],
                locationId: $data['location_id'] ?? null,
                batchNumber: $data['batch_number'] ?? null,
                lotNumber: $data['lot_number'] ?? null,
                serialNumber: $data['serial_number'] ?? null,
                quantity: (float) ($data['quantity'] ?? 0.0),
                reservedQuantity: (float) ($data['reserved_quantity'] ?? 0.0),
                expiryDate: isset($data['expiry_date'])
                    ? new \DateTimeImmutable($data['expiry_date'])
                    : null,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->stockLevelRepository->save($level);

            return $level;
        });
    }

    public function updateStockLevel(string $tenantId, string $id, array $data): StockLevel
    {
        return DB::transaction(function () use ($tenantId, $id, $data): StockLevel {
            $existing = $this->getStockLevel($tenantId, $id);

            $updated = new StockLevel(
                id: $existing->id,
                tenantId: $existing->tenantId,
                productId: $data['product_id'] ?? $existing->productId,
                variantId: array_key_exists('variant_id', $data) ? $data['variant_id'] : $existing->variantId,
                warehouseId: $data['warehouse_id'] ?? $existing->warehouseId,
                locationId: array_key_exists('location_id', $data) ? $data['location_id'] : $existing->locationId,
                batchNumber: array_key_exists('batch_number', $data) ? $data['batch_number'] : $existing->batchNumber,
                lotNumber: array_key_exists('lot_number', $data) ? $data['lot_number'] : $existing->lotNumber,
                serialNumber: array_key_exists('serial_number', $data) ? $data['serial_number'] : $existing->serialNumber,
                quantity: isset($data['quantity']) ? (float) $data['quantity'] : $existing->quantity,
                reservedQuantity: isset($data['reserved_quantity'])
                    ? (float) $data['reserved_quantity']
                    : $existing->reservedQuantity,
                expiryDate: isset($data['expiry_date'])
                    ? new \DateTimeImmutable($data['expiry_date'])
                    : $existing->expiryDate,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->stockLevelRepository->save($updated);

            return $updated;
        });
    }

    public function adjustQuantity(string $tenantId, string $id, float $delta): StockLevel
    {
        return DB::transaction(function () use ($tenantId, $id, $delta): StockLevel {
            $existing = $this->getStockLevel($tenantId, $id);

            $updated = new StockLevel(
                id: $existing->id,
                tenantId: $existing->tenantId,
                productId: $existing->productId,
                variantId: $existing->variantId,
                warehouseId: $existing->warehouseId,
                locationId: $existing->locationId,
                batchNumber: $existing->batchNumber,
                lotNumber: $existing->lotNumber,
                serialNumber: $existing->serialNumber,
                quantity: $existing->quantity + $delta,
                reservedQuantity: $existing->reservedQuantity,
                expiryDate: $existing->expiryDate,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->stockLevelRepository->save($updated);

            Event::dispatch(new StockLevelAdjusted($updated, $delta));

            return $updated;
        });
    }

    public function reserveQuantity(string $tenantId, string $id, float $qty): StockLevel
    {
        return DB::transaction(function () use ($tenantId, $id, $qty): StockLevel {
            $existing = $this->getStockLevel($tenantId, $id);

            $updated = new StockLevel(
                id: $existing->id,
                tenantId: $existing->tenantId,
                productId: $existing->productId,
                variantId: $existing->variantId,
                warehouseId: $existing->warehouseId,
                locationId: $existing->locationId,
                batchNumber: $existing->batchNumber,
                lotNumber: $existing->lotNumber,
                serialNumber: $existing->serialNumber,
                quantity: $existing->quantity,
                reservedQuantity: $existing->reservedQuantity + $qty,
                expiryDate: $existing->expiryDate,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->stockLevelRepository->save($updated);

            return $updated;
        });
    }

    public function releaseReservation(string $tenantId, string $id, float $qty): StockLevel
    {
        return DB::transaction(function () use ($tenantId, $id, $qty): StockLevel {
            $existing = $this->getStockLevel($tenantId, $id);

            $updated = new StockLevel(
                id: $existing->id,
                tenantId: $existing->tenantId,
                productId: $existing->productId,
                variantId: $existing->variantId,
                warehouseId: $existing->warehouseId,
                locationId: $existing->locationId,
                batchNumber: $existing->batchNumber,
                lotNumber: $existing->lotNumber,
                serialNumber: $existing->serialNumber,
                quantity: $existing->quantity,
                reservedQuantity: $existing->reservedQuantity - $qty,
                expiryDate: $existing->expiryDate,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->stockLevelRepository->save($updated);

            return $updated;
        });
    }

    public function deleteStockLevel(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getStockLevel($tenantId, $id);
            $this->stockLevelRepository->delete($tenantId, $id);
        });
    }
}
