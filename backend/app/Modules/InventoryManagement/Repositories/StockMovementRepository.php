<?php

namespace App\Modules\InventoryManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\InventoryManagement\Models\StockMovement;

class StockMovementRepository extends BaseRepository
{
    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }

    /**
     * Search stock movements by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['movement_type'])) {
            $query->where('movement_type', $criteria['movement_type']);
        }

        if (!empty($criteria['inventory_item_id'])) {
            $query->where('inventory_item_id', $criteria['inventory_item_id']);
        }

        if (!empty($criteria['date_from'])) {
            $query->where('movement_date', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->where('movement_date', '<=', $criteria['date_to']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['inventoryItem', 'user'])
            ->orderBy('movement_date', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get movements by type
     */
    public function getByType(string $type)
    {
        return $this->model->where('movement_type', $type)->with(['inventoryItem'])->get();
    }

    /**
     * Get movements for inventory item
     */
    public function getForInventoryItem(int $inventoryItemId)
    {
        return $this->model->where('inventory_item_id', $inventoryItemId)
            ->with(['user'])
            ->orderBy('movement_date', 'desc')
            ->get();
    }

    /**
     * Get movements by date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->model->whereBetween('movement_date', [$startDate, $endDate])
            ->with(['inventoryItem', 'user'])
            ->orderBy('movement_date', 'desc')
            ->get();
    }

    /**
     * Get inbound movements
     */
    public function getInbound()
    {
        return $this->model->whereIn('movement_type', ['purchase', 'return', 'adjustment_in'])
            ->with(['inventoryItem'])
            ->orderBy('movement_date', 'desc')
            ->get();
    }

    /**
     * Get outbound movements
     */
    public function getOutbound()
    {
        return $this->model->whereIn('movement_type', ['sale', 'usage', 'adjustment_out'])
            ->with(['inventoryItem'])
            ->orderBy('movement_date', 'desc')
            ->get();
    }
}
