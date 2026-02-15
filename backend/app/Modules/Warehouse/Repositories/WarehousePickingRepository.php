<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Warehouse\Models\WarehousePicking;
use Illuminate\Database\Eloquent\Collection;

class WarehousePickingRepository extends BaseRepository implements WarehousePickingRepositoryInterface
{
    public function __construct(WarehousePicking $model)
    {
        parent::__construct($model);
    }

    public function findByNumber(string $pickingNumber): ?WarehousePicking
    {
        return $this->model->where('picking_number', $pickingNumber)->first();
    }

    public function getByStatus(string $status): Collection
    {
        return $this->model->status($status)
            ->with(['items', 'branch', 'assignedTo'])
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_date', 'asc')
            ->get();
    }

    public function getByAssignee(int $userId): Collection
    {
        return $this->model->assignedTo($userId)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->with(['items', 'branch'])
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_date', 'asc')
            ->get();
    }

    public function getPendingPickings(?int $branchId = null): Collection
    {
        $query = $this->model->pending()
            ->with(['items', 'branch'])
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_date', 'asc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    public function getByType(string $type): Collection
    {
        return $this->model->byType($type)
            ->with(['items', 'branch', 'assignedTo'])
            ->orderBy('scheduled_date', 'desc')
            ->get();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('scheduled_date', [$startDate, $endDate])
            ->with(['items', 'branch', 'assignedTo'])
            ->orderBy('scheduled_date', 'desc')
            ->get();
    }

    public function getWithItems(int $id): ?WarehousePicking
    {
        return $this->model->with([
            'items.product',
            'items.variant',
            'items.location',
            'items.unit',
            'branch',
            'assignedTo',
        ])->find($id);
    }

    public function search(array $filters = []): Collection
    {
        $query = $this->model->query();

        if (isset($filters['picking_number'])) {
            $query->where('picking_number', 'like', "%{$filters['picking_number']}%");
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['picking_type'])) {
            $query->where('picking_type', $filters['picking_type']);
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['from_date'])) {
            $query->where('scheduled_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('scheduled_date', '<=', $filters['to_date']);
        }

        return $query->with(['items', 'branch', 'assignedTo'])
            ->orderBy('scheduled_date', 'desc')
            ->get();
    }

    public function getPickingEfficiency(int $userId, string $startDate, string $endDate): array
    {
        $pickings = $this->model->assignedTo($userId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->with('items')
            ->get();

        $totalPickings = $pickings->count();
        $totalItems = $pickings->sum(function($picking) {
            return $picking->items->count();
        });
        
        $totalTimeMinutes = 0;
        foreach ($pickings as $picking) {
            if ($picking->started_at && $picking->completed_at) {
                $totalTimeMinutes += $picking->started_at->diffInMinutes($picking->completed_at);
            }
        }

        $avgTimePerPicking = $totalPickings > 0 ? round($totalTimeMinutes / $totalPickings, 2) : 0;
        $avgItemsPerPicking = $totalPickings > 0 ? round($totalItems / $totalPickings, 2) : 0;

        return [
            'total_pickings' => $totalPickings,
            'total_items' => $totalItems,
            'total_time_minutes' => $totalTimeMinutes,
            'avg_time_per_picking' => $avgTimePerPicking,
            'avg_items_per_picking' => $avgItemsPerPicking,
        ];
    }
}
