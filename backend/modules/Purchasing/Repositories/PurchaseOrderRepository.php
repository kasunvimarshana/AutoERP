<?php

declare(strict_types=1);

namespace Modules\Purchasing\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Purchasing\Models\PurchaseOrder;

/**
 * Purchase Order Repository
 *
 * Handles data access for purchase orders.
 */
class PurchaseOrderRepository extends BaseRepository
{
    protected function model(): string
    {
        return PurchaseOrder::class;
    }

    /**
     * Get all purchase orders with filters and pagination.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery()->with(['supplier', 'items']);

        // Apply filters
        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('order_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('order_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_number', 'like', "%{$filters['search']}%")
                    ->orWhereHas('supplier', function ($sq) use ($filters) {
                        $sq->where('name', 'like', "%{$filters['search']}%");
                    });
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'order_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Find a purchase order by ID with relationships.
     */
    public function findWithRelations(int $id): ?PurchaseOrder
    {
        return $this->newQuery()->with(['supplier', 'items'])->find($id);
    }
}
