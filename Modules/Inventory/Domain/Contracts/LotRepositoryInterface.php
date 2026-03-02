<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Domain\Entities\InventoryLot;

interface LotRepositoryInterface
{
    public function save(InventoryLot $lot): InventoryLot;

    public function findById(int $tenantId, int $id): ?InventoryLot;

    public function findAll(int $tenantId, ?int $productId, ?int $warehouseId, int $page, int $perPage): array;

    public function delete(int $tenantId, int $id): void;
}
