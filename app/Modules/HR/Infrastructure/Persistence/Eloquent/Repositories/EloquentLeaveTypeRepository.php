<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\LeaveType;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveTypeModel;

class EloquentLeaveTypeRepository implements LeaveTypeRepositoryInterface
{
    public function __construct(private readonly LeaveTypeModel $model) {}

    private function toEntity(LeaveTypeModel $m): LeaveType
    {
        return new LeaveType(
            $m->id,
            $m->tenant_id,
            $m->name,
            $m->code,
            $m->description,
            (int) $m->default_days,
            (bool) $m->is_paid,
            (bool) $m->requires_approval,
            (bool) $m->is_active,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?LeaveType
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByCode(int $tenantId, string $code): ?LeaveType
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function findAllActiveByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): LeaveType
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?LeaveType
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) {
            return null;
        }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool) $m->delete() : false;
    }
}
