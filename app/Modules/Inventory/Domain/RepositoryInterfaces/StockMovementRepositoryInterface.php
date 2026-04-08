<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\StockMovement;

interface StockMovementRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?StockMovement;

    /** @return StockMovement[] */
    public function findByProduct(string $tenantId, string $productId): array;

    /** @return StockMovement[] */
    public function findByWarehouse(string $tenantId, string $warehouseId): array;

    /** @return StockMovement[] */
    public function findByReference(string $tenantId, string $referenceType, string $referenceId): array;

    public function save(StockMovement $movement): void;
}
