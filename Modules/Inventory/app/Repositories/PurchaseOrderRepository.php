<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\PurchaseOrder;

/**
 * Purchase Order Repository
 *
 * Handles data access for PurchaseOrder model
 */
class PurchaseOrderRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new PurchaseOrder;
    }

    /**
     * Find by PO number
     */
    public function findByPONumber(string $poNumber): ?PurchaseOrder
    {
        /** @var PurchaseOrder|null */
        return $this->findOneBy(['po_number' => $poNumber]);
    }

    /**
     * Get POs by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->byStatus($status)
            ->with(['supplier', 'branch', 'items'])
            ->orderBy('order_date', 'desc')
            ->get();
    }

    /**
     * Get POs for a supplier
     */
    public function getBySupplier(int $supplierId): Collection
    {
        return $this->model->where('supplier_id', $supplierId)
            ->with(['branch', 'items'])
            ->orderBy('order_date', 'desc')
            ->get();
    }

    /**
     * Get POs for a branch
     */
    public function getByBranch(int $branchId): Collection
    {
        return $this->model->where('branch_id', $branchId)
            ->with(['supplier', 'items'])
            ->orderBy('order_date', 'desc')
            ->get();
    }

    /**
     * Search purchase orders
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): Collection
    {
        $query = $this->model->newQuery()->with(['supplier', 'branch']);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('order_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('order_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('order_date', 'desc')->get();
    }

    /**
     * Check if PO number exists
     */
    public function poNumberExists(string $poNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->where('po_number', $poNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get with items
     */
    public function findWithItems(int $id): ?PurchaseOrder
    {
        /** @var PurchaseOrder|null */
        return $this->model->with(['items.inventoryItem', 'supplier', 'branch'])->find($id);
    }
}
