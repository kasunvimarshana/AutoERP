<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\AllocateStockData;

interface AllocateStockServiceInterface
{
    /**
     * Allocate (reserve) stock across InventoryLevel records using the configured rotation strategy.
     *
     * Returns an array of ['level_id' => int, 'quantity' => float] allocation records.
     *
     * @return array<int, array{level_id: int, quantity: float}>
     * @throws \DomainException if insufficient available stock
     */
    public function execute(AllocateStockData $data): array;
}
