<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Analytics\Domain\Entities\AnalyticsSnapshot;
use Modules\Analytics\Domain\RepositoryInterfaces\AnalyticsSnapshotRepositoryInterface;
use Modules\Analytics\Infrastructure\Persistence\Eloquent\Models\AnalyticsSnapshotModel;

class EloquentAnalyticsSnapshotRepository implements AnalyticsSnapshotRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?AnalyticsSnapshot
    {
        $model = AnalyticsSnapshotModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(int $tenantId, array $filters = []): array
    {
        $query = AnalyticsSnapshotModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);

        if (!empty($filters['summary_date'])) {
            $query->whereDate('summary_date', $filters['summary_date']);
        }
        if (!empty($filters['org_unit_id'])) {
            $query->where('org_unit_id', (int) $filters['org_unit_id']);
        }

        return $query->orderByDesc('summary_date')->orderByDesc('id')->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function save(AnalyticsSnapshot $snapshot): AnalyticsSnapshot
    {
        $data = [
            'tenant_id' => $snapshot->tenantId,
            'org_unit_id' => $snapshot->orgUnitId,
            'summary_date' => $snapshot->summaryDate,
            'total_rentals' => $snapshot->totalRentals,
            'completed_rentals' => $snapshot->completedRentals,
            'active_rentals' => $snapshot->activeRentals,
            'total_revenue' => $snapshot->totalRevenue,
            'total_service_cost' => $snapshot->totalServiceCost,
            'net_revenue' => $snapshot->netRevenue,
            'metadata' => $snapshot->metadata,
            'is_active' => $snapshot->isActive,
        ];

        if ($snapshot->id === null) {
            $data['row_version'] = 1;
            $model = AnalyticsSnapshotModel::create($data);
        } else {
            $model = AnalyticsSnapshotModel::withoutGlobalScope('tenant')->findOrFail($snapshot->id);
            $model->update($data);
            $model->increment('row_version');
            $model->refresh();
        }

        return $this->toEntity($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        AnalyticsSnapshotModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    private function toEntity(AnalyticsSnapshotModel $model): AnalyticsSnapshot
    {
        return new AnalyticsSnapshot(
            id: $model->id,
            tenantId: (int) $model->tenant_id,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            summaryDate: $model->summary_date?->format('Y-m-d') ?? '',
            totalRentals: (int) $model->total_rentals,
            completedRentals: (int) $model->completed_rentals,
            activeRentals: (int) $model->active_rentals,
            totalRevenue: (string) $model->total_revenue,
            totalServiceCost: (string) $model->total_service_cost,
            netRevenue: (string) $model->net_revenue,
            metadata: $model->metadata,
            isActive: (bool) $model->is_active,
            rowVersion: (int) $model->row_version,
        );
    }
}
