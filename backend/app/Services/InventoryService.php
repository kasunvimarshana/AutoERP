<?php

namespace App\Services;

use App\Events\InventoryUpdatedEvent;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Repositories\InventoryRepository;
use Illuminate\Database\Eloquent\Collection;

class InventoryService extends BaseService
{
    public function __construct(protected InventoryRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getForProduct(int $productId): ?Inventory
    {
        return $this->repository->findByProduct($productId);
    }

    public function stockIn(int $productId, int $qty, array $meta = []): Inventory
    {
        $inventory = $this->repository->adjustQuantity(
            $productId,
            $qty,
            InventoryTransaction::TYPE_IN,
            $meta
        );

        event(new InventoryUpdatedEvent($inventory, 'stock_in', $qty));

        return $inventory;
    }

    public function stockOut(int $productId, int $qty, array $meta = []): Inventory
    {
        $inventory = $this->repository->adjustQuantity(
            $productId,
            -$qty,
            InventoryTransaction::TYPE_OUT,
            $meta
        );

        event(new InventoryUpdatedEvent($inventory, 'stock_out', $qty));

        return $inventory;
    }

    public function adjustStock(int $productId, int $newQuantity, string $notes = ''): Inventory
    {
        $current   = $this->repository->findByProduct($productId);
        $diff      = $newQuantity - ($current ? $current->quantity : 0);
        $inventory = $this->repository->adjustQuantity(
            $productId,
            $diff,
            InventoryTransaction::TYPE_ADJUSTED,
            ['notes' => $notes]
        );

        event(new InventoryUpdatedEvent($inventory, 'adjusted', $diff));

        return $inventory;
    }

    public function reserve(int $productId, int $qty): Inventory
    {
        return $this->repository->reserveQuantity($productId, $qty);
    }

    public function release(int $productId, int $qty): Inventory
    {
        return $this->repository->releaseReservation($productId, $qty);
    }

    public function getLowStock(int $threshold = 10): Collection
    {
        return $this->repository->newQuery()
                                ->with('product')
                                ->where('quantity', '<=', $threshold)
                                ->where('quantity', '>', 0)
                                ->get();
    }

    public function getOutOfStock(): Collection
    {
        return $this->repository->newQuery()
                                ->with('product')
                                ->where('quantity', 0)
                                ->get();
    }
}
