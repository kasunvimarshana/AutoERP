<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;
use Modules\Auth\Application\Contracts\TenantContextResolverInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

/**
 * Eloquent-backed repository for auth-specific user lookups.
 *
 * Lives in the Auth module's infrastructure layer so that the application
 * layer (services, strategies) depends only on AuthUserRepositoryInterface.
 */
class EloquentAuthUserRepository implements AuthUserRepositoryInterface
{
    public function __construct(
        private readonly UserModel $model,
        private readonly TenantContextResolverInterface $tenantContextResolver,
    ) {}

    public function findForPassport(int $userId): ?OAuthenticatable
    {
        /** @var OAuthenticatable|null $user */
        $user = $this->query()->find($userId);

        return $user;
    }

    public function findAuthenticatable(int $userId): ?Authenticatable
    {
        return $this->query()->find($userId);
    }

    public function getRolesWithPermissions(int $userId): array
    {
        $user = $this->query()->with('roles.permissions')->find($userId);

        if (! $user) {
            return [];
        }

        return $user->roles->map(fn ($role) => [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ])->toArray();
    }

    public function hasRole(int $userId, string $role): bool
    {
        $roles = $this->getRolesWithPermissions($userId);
        $normalizedRole = $this->normalize($role);

        foreach ($roles as $entry) {
            if ($this->normalize((string) ($entry['name'] ?? '')) === $normalizedRole) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $roles = $this->getRolesWithPermissions($userId);
        $normalizedPermission = $this->normalize($permission);

        foreach ($roles as $entry) {
            $permissions = is_array($entry['permissions'] ?? null) ? $entry['permissions'] : [];
            foreach ($permissions as $rolePermission) {
                if ($this->normalize((string) $rolePermission) === $normalizedPermission) {
                    return true;
                }
            }
        }

        return false;
    }

    private function query(): Builder
    {
        $query = $this->model->newQuery();
        $tenantId = $this->tenantContextResolver->resolveTenantId();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim($value));
    }
}
