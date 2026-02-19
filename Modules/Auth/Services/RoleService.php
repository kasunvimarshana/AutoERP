<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Audit\Services\AuditService;
use Modules\Auth\Exceptions\PermissionNotFoundException;
use Modules\Auth\Exceptions\RoleNotFoundException;
use Modules\Auth\Models\Role;
use Modules\Auth\Repositories\PermissionRepository;
use Modules\Auth\Repositories\RoleRepository;
use Modules\Core\Exceptions\BusinessRuleException;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Tenant\Services\TenantContext;

/**
 * Role Service
 *
 * Handles business logic for role management including creation,
 * updates, deletion, permission syncing, and audit logging.
 */
class RoleService
{
    /**
     * Allowed fields for role updates
     */
    private const ALLOWED_UPDATE_FIELDS = ['name', 'slug', 'description', 'metadata'];

    /**
     * Create a new RoleService instance
     */
    public function __construct(
        protected RoleRepository $roleRepository,
        protected PermissionRepository $permissionRepository,
        protected AuditService $auditService,
        protected TenantContext $tenantContext
    ) {}

    /**
     * Create a new role
     *
     * @param  array  $data  Role data including name, slug, description, etc.
     * @return Role Created role instance
     *
     * @throws BusinessRuleException When tenant context is missing or slug exists
     * @throws PermissionNotFoundException When permissions are not found
     */
    public function createRole(array $data): Role
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (! $tenantId) {
            throw new BusinessRuleException('Tenant context is required for this operation.');
        }

        if (isset($data['slug']) && $this->roleRepository->slugExists($data['slug'], $tenantId)) {
            throw new BusinessRuleException("Role with slug '{$data['slug']}' already exists.");
        }

        if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
            $this->validatePermissions($data['permission_ids'], $tenantId);
        }

        $role = TransactionHelper::execute(function () use ($data, $tenantId) {
            $roleData = [
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'is_system' => $data['is_system'] ?? false,
            ];

            $role = $this->roleRepository->create($roleData);

            if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
                $this->roleRepository->syncPermissions($role->id, $data['permission_ids']);
            }

            return $role;
        });

        $this->auditService->logEvent(
            'role.created',
            Role::class,
            $role->id,
            ['name' => $role->name, 'slug' => $role->slug]
        );

        return $role->load('permissions');
    }

    /**
     * Update an existing role
     *
     * @param  string  $roleId  Role ID
     * @param  array  $data  Role data to update
     * @return Role Updated role instance
     *
     * @throws RoleNotFoundException When role is not found
     * @throws BusinessRuleException When slug exists or role is system role
     * @throws PermissionNotFoundException When permissions are not found
     */
    public function updateRole(string $roleId, array $data): Role
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (! $tenantId) {
            throw new BusinessRuleException('Tenant context is required for this operation.');
        }

        $role = $this->roleRepository->find($roleId);

        if (! $role) {
            throw new RoleNotFoundException("Role with ID {$roleId} not found.");
        }

        if ($role->tenant_id !== $tenantId) {
            throw new BusinessRuleException('Role does not belong to the current tenant.');
        }

        if ($role->is_system && isset($data['is_system']) && ! $data['is_system']) {
            throw new BusinessRuleException('Cannot modify system role status.');
        }

        if (isset($data['slug']) && $this->roleRepository->slugExists($data['slug'], $tenantId, $roleId)) {
            throw new BusinessRuleException("Role with slug '{$data['slug']}' already exists.");
        }

        if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
            $this->validatePermissions($data['permission_ids'], $tenantId);
        }

        TransactionHelper::execute(function () use ($role, $data) {
            $updateData = [];

            foreach (self::ALLOWED_UPDATE_FIELDS as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (! empty($updateData)) {
                $this->roleRepository->update($role->id, $updateData);
            }

            if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
                $this->roleRepository->syncPermissions($role->id, $data['permission_ids']);
            }
        });

        $this->auditService->logEvent(
            'role.updated',
            Role::class,
            $role->id,
            [
                'updated_fields' => array_keys(
                    array_intersect_key($data, array_flip(self::ALLOWED_UPDATE_FIELDS))
                ),
            ]
        );

        return $role->fresh(['permissions']);
    }

    /**
     * Delete a role
     *
     * @param  string  $roleId  Role ID
     * @return void
     *
     * @throws RoleNotFoundException When role is not found
     * @throws BusinessRuleException When role is system role or has users
     */
    public function deleteRole(string $roleId): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (! $tenantId) {
            throw new BusinessRuleException('Tenant context is required for this operation.');
        }

        $role = $this->roleRepository->find($roleId);

        if (! $role) {
            throw new RoleNotFoundException("Role with ID {$roleId} not found.");
        }

        if ($role->tenant_id !== $tenantId) {
            throw new BusinessRuleException('Role does not belong to the current tenant.');
        }

        if ($role->is_system) {
            throw new BusinessRuleException('System roles cannot be deleted.');
        }

        $userCount = $this->roleRepository->countUsers($roleId);
        if ($userCount > 0) {
            throw new BusinessRuleException(
                "Cannot delete role. It is currently assigned to {$userCount} user(s)."
            );
        }

        $roleName = $role->name;

        TransactionHelper::execute(function () use ($roleId) {
            $this->roleRepository->deleteWithRelationships($roleId);
        });

        $this->auditService->logEvent(
            'role.deleted',
            Role::class,
            $roleId,
            ['name' => $roleName]
        );
    }

    /**
     * Sync permissions for a role
     *
     * @param  string  $roleId  Role ID
     * @param  array  $permissionIds  Array of permission IDs
     * @return Role Updated role instance with permissions
     *
     * @throws RoleNotFoundException When role is not found
     * @throws BusinessRuleException When role doesn't belong to tenant
     * @throws PermissionNotFoundException When permissions are not found
     */
    public function syncPermissions(string $roleId, array $permissionIds): Role
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (! $tenantId) {
            throw new BusinessRuleException('Tenant context is required for this operation.');
        }

        $role = $this->roleRepository->find($roleId);

        if (! $role) {
            throw new RoleNotFoundException("Role with ID {$roleId} not found.");
        }

        if ($role->tenant_id !== $tenantId) {
            throw new BusinessRuleException('Role does not belong to the current tenant.');
        }

        $this->validatePermissions($permissionIds, $tenantId);

        TransactionHelper::execute(function () use ($role, $permissionIds) {
            $this->roleRepository->syncPermissions($role->id, $permissionIds);
        });

        $this->auditService->logEvent(
            'role.permissions.synced',
            Role::class,
            $role->id,
            ['permission_count' => count($permissionIds)]
        );

        return $role->fresh(['permissions']);
    }

    /**
     * Validate permissions exist and belong to same tenant
     *
     * @param  array  $permissionIds  Array of permission IDs
     * @param  string  $tenantId  Tenant ID
     * @return void
     *
     * @throws PermissionNotFoundException When permissions are not found
     * @throws BusinessRuleException When permissions don't belong to tenant
     */
    public function validatePermissions(array $permissionIds, string $tenantId): void
    {
        if (empty($permissionIds)) {
            return;
        }

        // Query all permissions by IDs
        $permissions = $this->permissionRepository->findBy(['id' => $permissionIds]);

        if ($permissions->count() !== count($permissionIds)) {
            $foundIds = $permissions->pluck('id')->toArray();
            $missingIds = array_diff($permissionIds, $foundIds);
            throw new PermissionNotFoundException(
                'Permission(s) not found: ' . implode(', ', $missingIds)
            );
        }

        $invalidPermissions = $permissions->filter(function ($permission) use ($tenantId) {
            return $permission->tenant_id !== $tenantId;
        });

        if ($invalidPermissions->isNotEmpty()) {
            $invalidIds = $invalidPermissions->pluck('id')->toArray();
            throw new BusinessRuleException(
                'Permission(s) do not belong to the current tenant: ' . implode(', ', $invalidIds)
            );
        }
    }
}
