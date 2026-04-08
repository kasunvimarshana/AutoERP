<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Application\Contracts\RoleServiceInterface;
use Modules\Auth\Domain\Entities\Role;
use Modules\Auth\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

class RoleService implements RoleServiceInterface
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function createRole(string $tenantId, array $data): Role
    {
        return DB::transaction(function () use ($tenantId, $data): Role {
            $now = now();

            $role = new Role(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                name: $data['name'],
                guard: $data['guard'] ?? 'api',
                permissions: $data['permissions'] ?? [],
                createdAt: $now,
                updatedAt: $now,
            );

            $this->roleRepository->save($role);

            $saved = $this->roleRepository->findById($tenantId, $role->id);

            if ($saved === null) {
                throw new NotFoundException("Role could not be retrieved after creation.");
            }

            return $saved;
        });
    }

    public function updateRole(string $tenantId, string $id, array $data): Role
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Role {
            $existing = $this->roleRepository->findById($tenantId, $id);

            if ($existing === null) {
                throw new NotFoundException("Role with id [{$id}] not found.");
            }

            $updated = new Role(
                id: $existing->id,
                tenantId: $existing->tenantId,
                name: $data['name'] ?? $existing->name,
                guard: $data['guard'] ?? $existing->guard,
                permissions: $data['permissions'] ?? $existing->permissions,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->roleRepository->save($updated);

            $saved = $this->roleRepository->findById($tenantId, $id);

            if ($saved === null) {
                throw new NotFoundException("Role with id [{$id}] not found after update.");
            }

            return $saved;
        });
    }

    public function deleteRole(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            if ($this->roleRepository->findById($tenantId, $id) === null) {
                throw new NotFoundException("Role with id [{$id}] not found.");
            }

            $this->roleRepository->delete($tenantId, $id);
        });
    }

    public function getRole(string $tenantId, string $id): Role
    {
        $role = $this->roleRepository->findById($tenantId, $id);

        if ($role === null) {
            throw new NotFoundException("Role with id [{$id}] not found.");
        }

        return $role;
    }

    public function getAllRoles(string $tenantId): array
    {
        return $this->roleRepository->findAll($tenantId);
    }
}
