<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryContract;
use Modules\Inventory\Domain\Entities\StockItem;
use Modules\Inventory\Domain\Entities\StockReservation;
use Modules\Inventory\Domain\Entities\StockTransaction;

/**
 * Inventory repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class InventoryRepository extends AbstractRepository implements InventoryRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = StockItem::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByProduct(int $productId): Collection
    {
        return $this->query()->where('product_id', $productId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByWarehouse(int $warehouseId): Collection
    {
        return $this->query()->where('warehouse_id', $warehouseId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByFEFO(int $productId, int $warehouseId): Collection
    {
        return StockItem::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereNotNull('expiry_date')
            ->where('quantity_available', '>', '0')
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteReservation(int|string $id): bool
    {
        $reservation = StockReservation::query()->findOrFail($id);

        return (bool) $reservation->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function paginateTransactions(int $productId, int $perPage = 15): LengthAwarePaginator
    {
        return StockTransaction::query()
            ->where('product_id', $productId)
            ->paginate($perPage);
    }

    public function paginateStockItems(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findByFIFO(int $productId, int $warehouseId): Collection
    {
        return StockItem::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_available', '>', '0')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByLIFO(int $productId, int $warehouseId): Collection
    {
        return StockItem::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_available', '>', '0')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findStockItemById(int $id): StockItem
    {
        /** @var StockItem */
        return StockItem::query()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStockItem(int $id, array $data): StockItem
    {
        $item = $this->findStockItemById($id);
        $item->update($data);

        return $item->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteStockItem(int $id): bool
    {
        $item = $this->findStockItemById($id);

        return (bool) $item->delete();
    }
}
