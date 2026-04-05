<?php
declare(strict_types=1);
namespace Modules\Maintenance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Maintenance\Domain\Entities\MaintenanceSchedule;
use Modules\Maintenance\Domain\RepositoryInterfaces\MaintenanceScheduleRepositoryInterface;
use Modules\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceScheduleModel;

class EloquentMaintenanceScheduleRepository implements MaintenanceScheduleRepositoryInterface
{
    public function __construct(private readonly MaintenanceScheduleModel $model) {}

    public function findById(int $id): ?MaintenanceSchedule
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findDue(int $tenantId, \DateTimeInterface $asOf): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', $asOf->format('Y-m-d H:i:s')))
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->model->newQuery()->where('tenant_id', $tenantId)
            ->orderBy('name')->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): MaintenanceSchedule
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?MaintenanceSchedule
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    private function toEntity(MaintenanceScheduleModel $m): MaintenanceSchedule
    {
        return new MaintenanceSchedule(
            $m->id, $m->tenant_id, $m->name, $m->asset_id,
            $m->maintenance_type, (int) $m->frequency_value, $m->frequency_unit,
            $m->last_run_at, $m->next_run_at,
            (bool) $m->is_active, $m->created_at, $m->updated_at,
        );
    }
}
