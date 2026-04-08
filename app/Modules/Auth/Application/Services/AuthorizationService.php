<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Auth\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Auth\Domain\RepositoryInterfaces\UserRepositoryInterface;

final class AuthorizationService implements AuthorizationServiceInterface
{
    private const PERMISSIONS_CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function can(int $userId, string $ability, mixed $subject = null): bool
    {
        $permissions = $this->getUserPermissions($userId);

        return in_array($ability, $permissions, true);
    }

    /**
     * {@inheritdoc}
     */
    public function assignRole(int $userId, int $roleId, ?int $tenantId = null): void
    {
        DB::transaction(function () use ($userId, $roleId, $tenantId) {
            $user = $this->userRepository->findWithRoles($userId);

            if (! $user) {
                return;
            }

            $pivotData = [
                'tenant_id'   => $tenantId,
                'assigned_at' => now(),
            ];

            $user->roles()->syncWithoutDetaching([$roleId => $pivotData]);
        });

        $this->clearPermissionsCache($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeRole(int $userId, int $roleId, ?int $tenantId = null): void
    {
        DB::transaction(function () use ($userId, $roleId, $tenantId) {
            $user = $this->userRepository->findWithRoles($userId);

            if (! $user) {
                return;
            }

            if ($tenantId !== null) {
                $user->roles()
                    ->wherePivot('tenant_id', $tenantId)
                    ->detach($roleId);
            } else {
                $user->roles()->detach($roleId);
            }
        });

        $this->clearPermissionsCache($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        DB::transaction(function () use ($roleId, $permissionIds) {
            $role = $this->roleRepository->findWithPermissions($roleId);

            if (! $role) {
                return;
            }

            $role->permissions()->sync($permissionIds);
        });

        // Bust cache for all users that have this role — broad invalidation via tag
        $this->clearRolePermissionsCache($roleId);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPermissions(int $userId): array
    {
        $cacheKey = $this->permissionsCacheKey($userId);

        return Cache::remember($cacheKey, self::PERMISSIONS_CACHE_TTL, function () use ($userId) {
            $user = $this->userRepository->findWithRoles($userId);

            if (! $user) {
                return [];
            }

            $permissions = [];

            foreach ($user->roles as $role) {
                foreach ($role->permissions as $permission) {
                    $permissions[] = $permission->slug;
                }
            }

            return array_values(array_unique($permissions));
        });
    }

    private function permissionsCacheKey(int $userId): string
    {
        return "auth.user.{$userId}.permissions";
    }

    private function clearPermissionsCache(int $userId): void
    {
        Cache::forget($this->permissionsCacheKey($userId));
    }

    private function clearRolePermissionsCache(int $roleId): void
    {
        // Pattern-based invalidation; when using Redis with cache tags you would
        // tag entries and flush by tag. With the file/array driver we do a broad
        // flush of users holding this role.
        $role = $this->roleRepository->findWithUsers($roleId);

        if (! $role) {
            return;
        }

        foreach ($role->users ?? [] as $user) {
            $this->clearPermissionsCache((int) $user->id);
        }
    }
}
