<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Inventory\Domain\Entities\CycleCount;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountModel;

class EloquentCycleCountRepository implements CycleCountRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?CycleCount
    {
        $model = CycleCountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return CycleCountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(CycleCountModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByStatus(string $tenantId, string $status): array
    {
        return CycleCountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->map(fn(CycleCountModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(CycleCount $cycleCount): void
    {
        /** @var CycleCountModel $model */
        $model = CycleCountModel::withoutGlobalScopes()->findOrNew($cycleCount->id);
        $model->fill([
            'tenant_id'    => $cycleCount->tenantId,
            'warehouse_id' => $cycleCount->warehouseId,
            'location_id'  => $cycleCount->locationId,
            'status'       => $cycleCount->status,
            'scheduled_at' => $cycleCount->scheduledAt,
            'completed_at' => $cycleCount->completedAt,
            'notes'        => $cycleCount->notes,
        ]);
        if (!$model->exists) {
            $model->id = $cycleCount->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        CycleCountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(CycleCountModel $model): CycleCount
    {
        return new CycleCount(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            warehouseId: (string) $model->warehouse_id,
            locationId: $model->location_id !== null ? (string) $model->location_id : null,
            status: (string) $model->status,
            scheduledAt: new \DateTimeImmutable($model->scheduled_at->toDateTimeString()),
            completedAt: $model->completed_at !== null
                ? new \DateTimeImmutable($model->completed_at->toDateTimeString())
                : null,
            notes: $model->notes !== null ? (string) $model->notes : null,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
