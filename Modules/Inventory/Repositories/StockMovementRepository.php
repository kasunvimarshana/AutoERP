<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Enums\StockMovementType;
use Modules\Inventory\Models\StockMovement;

/**
 * Stock Movement Repository
 *
 * Handles data access operations for stock movement records.
 * Provides movement history, analytics, and specialized queries for inventory transactions.
 */
class StockMovementRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    protected function makeModel(): Model
    {
        return new StockMovement;
    }

    /**
     * Get movement history by product.
     *
     * @param  string  $productId  Product ID
     * @param  int  $perPage  Results per page
     */
    public function getByProduct(string $productId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('product_id', $productId)
            ->with(['product', 'fromWarehouse', 'toWarehouse', 'reference'])
            ->latest('movement_date')
            ->paginate($perPage);
    }

    /**
     * Get movement history by warehouse.
     *
     * @param  string  $warehouseId  Warehouse ID
     * @param  int  $perPage  Results per page
     */
    public function getByWarehouse(string $warehouseId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where(function ($query) use ($warehouseId) {
                $query->where('from_warehouse_id', $warehouseId)
                    ->orWhere('to_warehouse_id', $warehouseId);
            })
            ->with(['product', 'fromWarehouse', 'toWarehouse', 'reference'])
            ->latest('movement_date')
            ->paginate($perPage);
    }

    /**
     * Get movements by type.
     *
     * @param  StockMovementType  $type  Movement type
     * @param  int  $perPage  Results per page
     */
    public function getByType(StockMovementType $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('type', $type)
            ->with(['product', 'fromWarehouse', 'toWarehouse', 'reference'])
            ->latest('movement_date')
            ->paginate($perPage);
    }

    /**
     * Get movements by date range.
     *
     * @param  string  $fromDate  Start date (Y-m-d format)
     * @param  string  $toDate  End date (Y-m-d format)
     * @param  int  $perPage  Results per page
     */
    public function getByDateRange(string $fromDate, string $toDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->with(['product', 'fromWarehouse', 'toWarehouse', 'reference'])
            ->latest('movement_date')
            ->paginate($perPage);
    }

    /**
     * Get movements by product and warehouse.
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     * @param  int  $perPage  Results per page
     */
    public function getByProductAndWarehouse(
        string $productId,
        string $warehouseId,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->model
            ->where('product_id', $productId)
            ->where(function ($query) use ($warehouseId) {
                $query->where('from_warehouse_id', $warehouseId)
                    ->orWhere('to_warehouse_id', $warehouseId);
            })
            ->with(['product', 'fromWarehouse', 'toWarehouse', 'reference'])
            ->latest('movement_date')
            ->paginate($perPage);
    }

    /**
     * Get movements by reference.
     *
     * @param  string  $referenceType  Reference type (e.g., 'purchase_order', 'sales_order')
     * @param  string  $referenceId  Reference ID
     */
    public function getByReference(string $referenceType, string $referenceId): Collection
    {
        return $this->model
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->with(['product', 'fromWarehouse', 'toWarehouse'])
            ->latest('movement_date')
            ->get();
    }

    /**
     * Get receipts (incoming stock).
     *
     * @param  int  $perPage  Results per page
     */
    public function getReceipts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByType(StockMovementType::RECEIPT, $perPage);
    }

    /**
     * Get issues (outgoing stock).
     *
     * @param  int  $perPage  Results per page
     */
    public function getIssues(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByType(StockMovementType::ISSUE, $perPage);
    }

    /**
     * Get transfers between warehouses.
     *
     * @param  int  $perPage  Results per page
     */
    public function getTransfers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByType(StockMovementType::TRANSFER, $perPage);
    }

    /**
     * Get adjustments.
     *
     * @param  int  $perPage  Results per page
     */
    public function getAdjustments(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByType(StockMovementType::ADJUSTMENT, $perPage);
    }

    /**
     * Get movement analytics by product for date range.
     *
     * @param  string  $productId  Product ID
     * @param  string  $fromDate  Start date
     * @param  string  $toDate  End date
     * @return array Analytics data
     */
    public function getProductAnalytics(string $productId, string $fromDate, string $toDate): array
    {
        $movements = $this->model
            ->where('product_id', $productId)
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->get();

        $receipts = $movements->where('type', StockMovementType::RECEIPT);
        $issues = $movements->where('type', StockMovementType::ISSUE);
        $transfers = $movements->where('type', StockMovementType::TRANSFER);
        $adjustments = $movements->where('type', StockMovementType::ADJUSTMENT);

        return [
            'total_movements' => $movements->count(),
            'receipts' => [
                'count' => $receipts->count(),
                'quantity' => $receipts->sum(fn ($m) => (float) $m->quantity),
            ],
            'issues' => [
                'count' => $issues->count(),
                'quantity' => $issues->sum(fn ($m) => (float) $m->quantity),
            ],
            'transfers' => [
                'count' => $transfers->count(),
                'quantity' => $transfers->sum(fn ($m) => (float) $m->quantity),
            ],
            'adjustments' => [
                'count' => $adjustments->count(),
                'quantity' => $adjustments->sum(fn ($m) => (float) $m->quantity),
            ],
            'net_change' => $receipts->sum(fn ($m) => (float) $m->quantity)
                          - $issues->sum(fn ($m) => (float) $m->quantity),
        ];
    }

    /**
     * Get movement analytics by warehouse for date range.
     *
     * @param  string  $warehouseId  Warehouse ID
     * @param  string  $fromDate  Start date
     * @param  string  $toDate  End date
     * @return array Analytics data
     */
    public function getWarehouseAnalytics(string $warehouseId, string $fromDate, string $toDate): array
    {
        $incoming = $this->model
            ->where('to_warehouse_id', $warehouseId)
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->get();

        $outgoing = $this->model
            ->where('from_warehouse_id', $warehouseId)
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->get();

        return [
            'incoming' => [
                'count' => $incoming->count(),
                'quantity' => $incoming->sum(fn ($m) => (float) $m->quantity),
                'value' => $incoming->sum(fn ($m) => (float) $m->quantity * (float) ($m->cost ?? 0)),
            ],
            'outgoing' => [
                'count' => $outgoing->count(),
                'quantity' => $outgoing->sum(fn ($m) => (float) $m->quantity),
                'value' => $outgoing->sum(fn ($m) => (float) $m->quantity * (float) ($m->cost ?? 0)),
            ],
            'net_quantity' => $incoming->sum(fn ($m) => (float) $m->quantity)
                            - $outgoing->sum(fn ($m) => (float) $m->quantity),
        ];
    }

    /**
     * Get movement summary by type for date range.
     *
     * @param  string  $fromDate  Start date
     * @param  string  $toDate  End date
     * @return array Summary data grouped by type
     */
    public function getMovementSummaryByType(string $fromDate, string $toDate): array
    {
        $movements = $this->model
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->get()
            ->groupBy('type');

        $summary = [];
        foreach (StockMovementType::cases() as $type) {
            $typeMovements = $movements->get($type->value, collect());
            $summary[$type->value] = [
                'type' => $type->value,
                'label' => $type->label(),
                'count' => $typeMovements->count(),
                'quantity' => $typeMovements->sum(fn ($m) => (float) $m->quantity),
                'value' => $typeMovements->sum(fn ($m) => (float) $m->quantity * (float) ($m->cost ?? 0)),
            ];
        }

        return $summary;
    }

    /**
     * Search stock movements with filters.
     *
     * @param  array  $filters  Search filters
     * @param  int  $perPage  Results per page
     */
    public function searchMovements(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['product', 'fromWarehouse', 'toWarehouse', 'reference']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $query->whereIn('type', $filters['type']);
            } else {
                $query->where('type', $filters['type']);
            }
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['from_warehouse_id'])) {
            $query->where('from_warehouse_id', $filters['from_warehouse_id']);
        }

        if (! empty($filters['to_warehouse_id'])) {
            $query->where('to_warehouse_id', $filters['to_warehouse_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('from_warehouse_id', $filters['warehouse_id'])
                    ->orWhere('to_warehouse_id', $filters['warehouse_id']);
            });
        }

        if (! empty($filters['from_date'])) {
            $query->where('movement_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('movement_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        if (! empty($filters['reference_id'])) {
            $query->where('reference_id', $filters['reference_id']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        $sortField = $filters['sort_by'] ?? 'movement_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    /**
     * Get recent movements.
     *
     * @param  int  $limit  Number of records to return
     */
    public function getRecentMovements(int $limit = 10): Collection
    {
        return $this->model
            ->with(['product', 'fromWarehouse', 'toWarehouse'])
            ->latest('movement_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Update stock movement and return the updated model.
     *
     * @param  int|string  $id  Stock movement ID
     * @param  array  $data  Data to update
     */
    public function updateAndReturn(int|string $id, array $data): StockMovement
    {
        $movement = $this->findOrFail($id);
        $movement->update($data);

        return $movement->fresh(['product', 'fromWarehouse', 'toWarehouse', 'reference']);
    }

    /**
     * Get receipts for FIFO valuation (oldest first).
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     * @param  int  $limit  Maximum number of receipts
     */
    public function getReceiptsForFifo(string $productId, string $warehouseId, int $limit = 100): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('to_warehouse_id', $warehouseId)
            ->where('type', StockMovementType::RECEIPT)
            ->whereNotNull('cost')
            ->orderBy('movement_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get receipts for LIFO valuation (newest first).
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     * @param  int  $limit  Maximum number of receipts
     */
    public function getReceiptsForLifo(string $productId, string $warehouseId, int $limit = 100): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('to_warehouse_id', $warehouseId)
            ->where('type', StockMovementType::RECEIPT)
            ->whereNotNull('cost')
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
