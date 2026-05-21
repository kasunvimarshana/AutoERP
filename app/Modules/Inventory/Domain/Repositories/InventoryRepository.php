<?php

namespace Modules\Inventory\Domain\Repositories;

interface InventoryRepository
{
    public function getLayers(int $itemId, int $warehouseId): array;

    public function getBalance(int $itemId, int $warehouseId): object;

    public function addLayer(array $data): void;

    public function updateBalance(int $itemId, int $warehouseId, array $data): void;
}
