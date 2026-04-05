<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Domain\Entities\Permission;
use Modules\Authorization\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\PermissionModel;

class EloquentPermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(private readonly PermissionModel $model) {}

    private function toEntity(PermissionModel $m): Permission
    {
        return new Permission($m->id, $m->name, $m->slug, $m->module, $m->description, $m->created_at, $m->updated_at);
    }

    public function findById(int $id): ?Permission
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findAll(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function findByModule(string $module): array
    {
        return $this->model->newQuery()
            ->where('module', $module)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): Permission
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?Permission
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
