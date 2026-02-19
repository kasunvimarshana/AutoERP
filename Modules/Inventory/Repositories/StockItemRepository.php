<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Exceptions\StockItemNotFoundException;
use Modules\Inventory\Models\StockItem;

/**
 * Stock Item Repository
 *
 * Handles data access operations for stock item management.
 * Provides queries for inventory levels, stock valuation, and reorder analysis.
 */
class StockItemRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    protected function makeModel(): Model
    {
        return new StockItem;
    }

    /**
     * Find stock item by product and warehouse.
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     */
    public function findByProductAndWarehouse(string $productId, string $warehouseId): ?StockItem
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }

    /**
     * Find stock item by product and warehouse or fail.
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     *
     * @throws StockItemNotFoundException
     */
    public function findByProductAndWarehouseOrFail(string $productId, string $warehouseId): StockItem
    {
        $stockItem = $this->findByProductAndWarehouse($productId, $warehouseId);

        if (! $stockItem) {
            throw new StockItemNotFoundException(
                "Stock item for product {$productId} in warehouse {$warehouseId} not found"
            );
        }

        return $stockItem;
    }

    /**
     * Get stock items by product.
     *
     * @param  string  $productId  Product ID
     * @param  int  $perPage  Results per page
     */
    public function getByProduct(string $productId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('product_id', $productId)
            ->with(['product', 'warehouse', 'location'])
            ->orderBy('warehouse_id')
            ->paginate($perPage);
    }

    /**
     * Get stock items by warehouse.
     *
     * @param  string  $warehouseId  Warehouse ID
     * @param  int  $perPage  Results per page
     */
    public function getByWarehouse(string $warehouseId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('warehouse_id', $warehouseId)
            ->with(['product', 'warehouse', 'location'])
            ->orderBy('product_id')
            ->paginate($perPage);
    }

    /**
     * Get low stock items (below reorder point).
     *
     * @param  string  $tenantId  Tenant ID
     * @param  int  $perPage  Results per page
     */
    public function getLowStockItems(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->whereNotNull('reorder_point')
            ->whereRaw('CAST(available_quantity AS DECIMAL(10,2)) <= CAST(reorder_point AS DECIMAL(10,2))')
            ->with(['product', 'warehouse'])
            ->orderByRaw('CAST(available_quantity AS DECIMAL(10,2)) - CAST(reorder_point AS DECIMAL(10,2)) ASC')
            ->paginate($perPage);
    }

    /**
     * Get out of stock items.
     *
     * @param  int  $perPage  Results per page
     */
    public function getOutOfStockItems(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereRaw('CAST(available_quantity AS DECIMAL(10,2)) <= 0')
            ->with(['product', 'warehouse'])
            ->orderBy('product_id')
            ->paginate($perPage);
    }

    /**
     * Get overstock items (above maximum quantity).
     *
     * @param  int  $perPage  Results per page
     */
    public function getOverstockItems(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereNotNull('maximum_quantity')
            ->whereRaw('CAST(quantity AS DECIMAL(10,2)) >= CAST(maximum_quantity AS DECIMAL(10,2))')
            ->with(['product', 'warehouse'])
            ->orderByRaw('CAST(quantity AS DECIMAL(10,2)) - CAST(maximum_quantity AS DECIMAL(10,2)) DESC')
            ->paginate($perPage);
    }

    /**
     * Get stock valuation by warehouse.
     *
     * @param  string  $warehouseId  Warehouse ID
     * @return array Valuation data
     */
    public function getStockValuationByWarehouse(string $warehouseId): array
    {
        $result = $this->model
            ->where('warehouse_id', $warehouseId)
            ->selectRaw('
                COUNT(*) as total_items,
                SUM(CAST(quantity AS DECIMAL(15,2))) as total_quantity,
                SUM(CAST(available_quantity AS DECIMAL(15,2))) as total_available,
                SUM(CAST(reserved_quantity AS DECIMAL(15,2))) as total_reserved,
                SUM(CAST(quantity AS DECIMAL(15,2)) * CAST(average_cost AS DECIMAL(15,2))) as total_value
            ')
            ->first();

        return [
            'total_items' => $result->total_items ?? 0,
            'total_quantity' => $result->total_quantity ?? '0',
            'total_available' => $result->total_available ?? '0',
            'total_reserved' => $result->total_reserved ?? '0',
            'total_value' => $result->total_value ?? '0',
        ];
    }

    /**
     * Get total stock valuation across all warehouses.
     *
     * @return array Valuation data
     */
    public function getTotalStockValuation(): array
    {
        $result = $this->model
            ->selectRaw('
                COUNT(*) as total_items,
                SUM(CAST(quantity AS DECIMAL(15,2))) as total_quantity,
                SUM(CAST(available_quantity AS DECIMAL(15,2))) as total_available,
                SUM(CAST(reserved_quantity AS DECIMAL(15,2))) as total_reserved,
                SUM(CAST(quantity AS DECIMAL(15,2)) * CAST(average_cost AS DECIMAL(15,2))) as total_value
            ')
            ->first();

        return [
            'total_items' => $result->total_items ?? 0,
            'total_quantity' => $result->total_quantity ?? '0',
            'total_available' => $result->total_available ?? '0',
            'total_reserved' => $result->total_reserved ?? '0',
            'total_value' => $result->total_value ?? '0',
        ];
    }

    /**
     * Get stock valuation by product.
     *
     * @param  string  $productId  Product ID
     * @return array Valuation data
     */
    public function getStockValuationByProduct(string $productId): array
    {
        $result = $this->model
            ->where('product_id', $productId)
            ->selectRaw('
                COUNT(*) as total_warehouses,
                SUM(CAST(quantity AS DECIMAL(15,2))) as total_quantity,
                SUM(CAST(available_quantity AS DECIMAL(15,2))) as total_available,
                SUM(CAST(reserved_quantity AS DECIMAL(15,2))) as total_reserved,
                AVG(CAST(average_cost AS DECIMAL(15,2))) as avg_cost,
                SUM(CAST(quantity AS DECIMAL(15,2)) * CAST(average_cost AS DECIMAL(15,2))) as total_value
            ')
            ->first();

        return [
            'total_warehouses' => $result->total_warehouses ?? 0,
            'total_quantity' => $result->total_quantity ?? '0',
            'total_available' => $result->total_available ?? '0',
            'total_reserved' => $result->total_reserved ?? '0',
            'average_cost' => $result->avg_cost ?? '0',
            'total_value' => $result->total_value ?? '0',
        ];
    }

    /**
     * Search stock items with filters and pagination.
     *
     * @param  array  $filters  Search filters
     * @param  int  $perPage  Results per page
     *
     * @throws \InvalidArgumentException if tenant_id is not provided
     */
    public function searchStockItems(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        // Enforce tenant isolation - tenant_id is required
        if (empty($filters['tenant_id'])) {
            throw new \InvalidArgumentException('tenant_id is required for stock item queries to maintain tenant isolation');
        }
        
        $query = $this->model->query()->with(['product', 'warehouse', 'location']);

        $query->where('tenant_id', $filters['tenant_id']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                })
                    ->orWhereHas('warehouse', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereNotNull('reorder_point')
                ->whereRaw('CAST(available_quantity AS DECIMAL(10,2)) <= CAST(reorder_point AS DECIMAL(10,2))');
        }

        if (isset($filters['out_of_stock']) && $filters['out_of_stock']) {
            $query->whereRaw('CAST(available_quantity AS DECIMAL(10,2)) <= 0');
        }

        if (isset($filters['overstock']) && $filters['overstock']) {
            $query->whereNotNull('maximum_quantity')
                ->whereRaw('CAST(quantity AS DECIMAL(10,2)) >= CAST(maximum_quantity AS DECIMAL(10,2))');
        }

        if (! empty($filters['min_quantity'])) {
            $query->whereRaw('CAST(quantity AS DECIMAL(10,2)) >= ?', [$filters['min_quantity']]);
        }

        if (! empty($filters['max_quantity'])) {
            $query->whereRaw('CAST(quantity AS DECIMAL(10,2)) <= ?', [$filters['max_quantity']]);
        }

        if (! empty($filters['organization_id'])) {
            $query->whereHas('warehouse', function ($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        $sortField = $filters['sort_by'] ?? 'product_id';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    /**
     * Get items requiring reorder.
     *
     * @param  int  $perPage  Results per page
     */
    public function getItemsRequiringReorder(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereNotNull('reorder_point')
            ->whereNotNull('reorder_quantity')
            ->whereRaw('CAST(available_quantity AS DECIMAL(10,2)) <= CAST(reorder_point AS DECIMAL(10,2))')
            ->with(['product', 'warehouse'])
            ->orderByRaw('CAST(available_quantity AS DECIMAL(10,2)) - CAST(reorder_point AS DECIMAL(10,2)) ASC')
            ->paginate($perPage);
    }

    /**
     * Update stock item and return the updated model.
     *
     * @param  int|string  $id  Stock item ID
     * @param  array  $data  Data to update
     */
    public function updateAndReturn(int|string $id, array $data): StockItem
    {
        $stockItem = $this->findOrFail($id);
        $stockItem->update($data);

        return $stockItem->fresh(['product', 'warehouse', 'location']);
    }
}
