<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\StockMovement;

/**
 * Stock Movement Repository
 *
 * Handles data access for StockMovement model
 */
class StockMovementRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new StockMovement;
    }

    /**
     * Get movements for an item
     */
    public function getByItem(int $itemId): Collection
    {
        return $this->model->where('item_id', $itemId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get movements by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->byType($type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get movements by date range
     */
    public function getByDateRange(string $from, string $to): Collection
    {
        return $this->model->dateRange($from, $to)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get movements for a branch
     */
    public function getByBranch(int $branchId): Collection
    {
        return $this->model->where('branch_id', $branchId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent movements
     */
    public function getRecent(int $limit = 50): Collection
    {
        return $this->model->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search movements
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): Collection
    {
        $query = $this->model->newQuery();

        if (! empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['movement_type'])) {
            $query->byType($filters['movement_type']);
        }

        if (! empty($filters['from_date']) && ! empty($filters['to_date'])) {
            $query->dateRange($filters['from_date'], $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
