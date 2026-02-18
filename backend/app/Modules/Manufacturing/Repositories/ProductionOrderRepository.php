<?php

namespace App\Modules\Manufacturing\Repositories;

use App\Modules\Manufacturing\Enums\ProductionOrderPriority;
use App\Modules\Manufacturing\Enums\ProductionOrderStatus;
use App\Modules\Manufacturing\Models\ProductionOrder;
use App\Repositories\BaseRepository;

/**
 * Production Order Repository
 *
 * Handles data access for production orders.
 */
class ProductionOrderRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return ProductionOrder::class;
    }

    /**
     * Find production order by number
     */
    public function findByNumber(string $number): ?ProductionOrder
    {
        return $this->model->where('production_order_number', $number)->first();
    }

    /**
     * Get production orders by status
     */
    public function getByStatus(ProductionOrderStatus $status)
    {
        return $this->model
            ->byStatus($status)
            ->with(['product', 'billOfMaterial', 'warehouse', 'items.product'])
            ->orderBy('scheduled_start_date', 'asc')
            ->get();
    }

    /**
     * Get production orders by priority
     */
    public function getByPriority(ProductionOrderPriority $priority)
    {
        return $this->model
            ->byPriority($priority)
            ->with(['product', 'billOfMaterial', 'warehouse', 'items.product'])
            ->orderBy('scheduled_start_date', 'asc')
            ->get();
    }

    /**
     * Get in-progress production orders
     */
    public function getInProgress()
    {
        return $this->model
            ->inProgress()
            ->with(['product', 'billOfMaterial', 'warehouse', 'items.product'])
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_start_date', 'asc')
            ->get();
    }

    /**
     * Find with all relationships loaded
     */
    public function findWithRelations(int $id): ProductionOrder
    {
        return $this->model
            ->with([
                'product',
                'billOfMaterial.items.componentProduct',
                'warehouse',
                'items.product',
                'items.uom',
                'workOrders',
                'creator',
                'releaser',
                'completer',
            ])
            ->findOrFail($id);
    }

    /**
     * Search production orders
     */
    public function search(string $search)
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('production_order_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            })
            ->with(['product', 'billOfMaterial', 'warehouse'])
            ->get();
    }

    /**
     * Get scheduled production orders for date range
     */
    public function getScheduledBetween(string $startDate, string $endDate)
    {
        return $this->model
            ->whereBetween('scheduled_start_date', [$startDate, $endDate])
            ->with(['product', 'billOfMaterial', 'warehouse'])
            ->orderBy('scheduled_start_date', 'asc')
            ->get();
    }

    /**
     * Get overdue production orders
     */
    public function getOverdue()
    {
        return $this->model
            ->where('scheduled_end_date', '<', now())
            ->whereIn('status', [
                ProductionOrderStatus::RELEASED->value,
                ProductionOrderStatus::IN_PROGRESS->value,
            ])
            ->with(['product', 'billOfMaterial', 'warehouse'])
            ->orderBy('scheduled_end_date', 'asc')
            ->get();
    }
}
