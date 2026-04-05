<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\Position;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PositionModel;

class EloquentPositionRepository implements PositionRepositoryInterface
{
    public function __construct(private readonly PositionModel $model) {}

    private function toEntity(PositionModel $m): Position
    {
        return new Position(
            $m->id,
            $m->tenant_id,
            $m->department_id,
            $m->title,
            $m->code,
            $m->description,
            $m->employment_type,
            $m->min_salary !== null ? (float) $m->min_salary : null,
            $m->max_salary !== null ? (float) $m->max_salary : null,
            (bool) $m->is_active,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?Position
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function findByDepartment(int $departmentId): array
    {
        return $this->model->newQuery()
            ->where('department_id', $departmentId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): Position
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?Position
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
