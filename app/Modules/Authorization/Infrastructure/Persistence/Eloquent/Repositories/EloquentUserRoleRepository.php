<?php
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Authorization\Domain\Entities\UserRole;
use Modules\Authorization\Domain\RepositoryInterfaces\UserRoleRepositoryInterface;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;

class EloquentUserRoleRepository implements UserRoleRepositoryInterface
{
    public function __construct(private readonly UserRoleModel $model) {}

    public function assign(int $userId, int $roleId): UserRole
    {
        $m = $this->model->firstOrCreate([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
        return $this->toEntity($m);
    }

    public function revoke(int $userId, int $roleId): bool
    {
        return (bool) $this->model
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete();
    }

    public function getRoleIdsForUser(int $userId): array
    {
        return $this->model->where('user_id', $userId)->pluck('role_id')->toArray();
    }

    private function toEntity(object $m): UserRole
    {
        return new UserRole(id: $m->id, userId: $m->user_id, roleId: $m->role_id);
    }
}
