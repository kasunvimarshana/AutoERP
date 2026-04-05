<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Authorization\Domain\Entities\Role;
use Modules\Authorization\Domain\RepositoryInterfaces\UserRoleRepositoryInterface;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\UserRoleModel;

class EloquentUserRoleRepository implements UserRoleRepositoryInterface
{
    public function __construct(
        private readonly UserRoleModel $userRoleModel,
        private readonly RoleModel $roleModel,
    ) {}

    private function toRoleEntity(RoleModel $m): Role
    {
        return new Role($m->id, $m->tenant_id, $m->name, $m->slug, $m->description, $m->created_at, $m->updated_at);
    }

    public function getUserRoles(int $userId): array
    {
        $roleIds = $this->userRoleModel->newQuery()
            ->where('user_id', $userId)
            ->pluck('role_id');
        return $this->roleModel->newQuery()
            ->whereIn('id', $roleIds)
            ->get()
            ->map(fn($m) => $this->toRoleEntity($m))
            ->all();
    }

    public function assignRole(int $userId, int $roleId): void
    {
        $this->userRoleModel->newQuery()->firstOrCreate([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
    }

    public function removeRole(int $userId, int $roleId): void
    {
        $this->userRoleModel->newQuery()
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete();
    }

    public function syncRoles(int $userId, array $roleIds): void
    {
        $this->userRoleModel->newQuery()->where('user_id', $userId)->delete();
        foreach ($roleIds as $roleId) {
            $this->userRoleModel->newQuery()->create([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function userHasPermission(int $userId, string $permissionSlug): bool
    {
        return DB::table('user_roles')
            ->join('role_permissions', 'user_roles.role_id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('user_roles.user_id', $userId)
            ->where('permissions.slug', $permissionSlug)
            ->whereNull('permissions.deleted_at')
            ->exists();
    }

    public function userHasRole(int $userId, string $roleSlug): bool
    {
        return DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.slug', $roleSlug)
            ->whereNull('roles.deleted_at')
            ->exists();
    }
}
