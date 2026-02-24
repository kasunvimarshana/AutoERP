<?php

namespace Modules\Inventory\Infrastructure\Repositories;

use Modules\Inventory\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Inventory\Infrastructure\Models\CycleCountLineModel;
use Modules\Inventory\Infrastructure\Models\CycleCountModel;

class CycleCountRepository implements CycleCountRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return CycleCountModel::find($id);
    }

    public function findLineById(string $lineId): ?object
    {
        return CycleCountLineModel::find($lineId);
    }

    public function create(array $data): object
    {
        return CycleCountModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $count = CycleCountModel::findOrFail($id);
        $count->update($data);
        return $count->fresh();
    }

    public function createLine(array $data): object
    {
        return CycleCountLineModel::create($data);
    }

    public function updateLine(string $lineId, array $data): object
    {
        $line = CycleCountLineModel::findOrFail($lineId);
        $line->update($data);
        return $line->fresh();
    }

    public function linesForCount(string $cycleCountId): array
    {
        return CycleCountLineModel::where('cycle_count_id', $cycleCountId)
            ->orderBy('created_at')
            ->get()
            ->all();
    }

    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object
    {
        $query = CycleCountModel::where('tenant_id', $tenantId)
            ->orderByDesc('count_date');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['count_date_from'])) {
            $query->where('count_date', '>=', $filters['count_date_from']);
        }

        if (! empty($filters['count_date_to'])) {
            $query->where('count_date', '<=', $filters['count_date_to']);
        }

        return $query->paginate($perPage);
    }
}
