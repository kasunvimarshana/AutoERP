<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Warehouse\Models\WarehousePutaway;
use Illuminate\Database\Eloquent\Collection;

class WarehousePutawayRepository extends BaseRepository implements WarehousePutawayRepositoryInterface
{
    public function __construct(WarehousePutaway $model)
    {
        parent::__construct($model);
    }

    public function findByNumber(string $putawayNumber): ?WarehousePutaway
    {
        return $this->model->where('putaway_number', $putawayNumber)->first();
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

    public function getPendingPutaways(?int $branchId = null): Collection
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

    public function getWithItems(int $id): ?WarehousePutaway
    {
        return $this->model->with([
            'items.product',
            'items.variant',
            'items.destinationLocation',
            'items.unit',
            'branch',
            'assignedTo',
        ])->find($id);
    }

    public function search(array $filters = []): Collection
    {
        $query = $this->model->query();

        if (isset($filters['putaway_number'])) {
            $query->where('putaway_number', 'like', "%{$filters['putaway_number']}%");
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['putaway_type'])) {
            $query->where('putaway_type', $filters['putaway_type']);
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
}
