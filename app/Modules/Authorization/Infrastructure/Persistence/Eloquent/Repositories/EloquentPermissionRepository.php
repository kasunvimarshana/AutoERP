<?php
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Authorization\Domain\Entities\Permission;
use Modules\Authorization\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentPermissionRepository extends EloquentRepository implements PermissionRepositoryInterface
{
    public function __construct(PermissionModel $model) { parent::__construct($model); }

    public function findById(int $id): ?Permission
    {
        $m = parent::findById($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByName(string $name): ?Permission
    {
        $m = $this->model->where('name', $name)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAll(int $perPage = 50): LengthAwarePaginator
    {
        return $this->model->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): Permission
    {
        return $this->toEntity(parent::create($data));
    }

    public function delete(Permission $permission): bool
    {
        $m = $this->model->findOrFail($permission->id);
        return parent::delete($m);
    }

    private function toEntity(object $m): Permission
    {
        return new Permission(
            id:          $m->id,
            name:        $m->name,
            guardName:   $m->guard_name,
            description: $m->description ?? null,
        );
    }
}
