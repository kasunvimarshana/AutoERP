<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Audit\Services\AuditService;
use Modules\Auth\Exceptions\PermissionNotFoundException;
use Modules\Auth\Models\Permission;
use Modules\Auth\Repositories\PermissionRepository;
use Modules\Core\Exceptions\BusinessRuleException;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Tenant\Services\TenantContext;

/**
 * Permission Service
 *
 * Handles business logic for permission management including creation,
 * updates, deletion, and audit logging.
 */
class PermissionService
{
    /**
     * Allowed fields for permission updates
     */
    private const ALLOWED_UPDATE_FIELDS = ['name', 'slug', 'description', 'resource', 'action', 'metadata'];

    /**
     * Create a new PermissionService instance
     */
    public function __construct(
        protected PermissionRepository $permissionRepository,
        protected AuditService $auditService,
        protected TenantContext $tenantContext
    ) {}

    /**
     * Create a new permission
     *
     * @param  array  $data  Permission data including name, slug, resource, action, etc.
     * @return Permission Created permission instance
     *
     * @throws BusinessRuleException When tenant context is missing or slug exists
     */
    public function createPermission(array $data): Permission
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (! $tenantId) {
            throw new BusinessRuleException('Tenant context is required for this operation.');
        }

        if (isset($data['slug']) && $this->permissionRepository->slugExists($data['slug'], $tenantId)) {
            throw new BusinessRuleException("Permission with slug '{$data['slug']}' already exists.");
        }

        $permission = TransactionHelper::execute(function () use ($data, $tenantId) {
            $permissionData = [
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'resource' => $data['resource'],
                'action' => $data['action'],
                'metadata' => $data['metadata'] ?? [],
                'is_system' => $data['is_system'] ?? false,
            ];

            return $this->permissionRepository->create($permissionData);
        });

        $this->auditService->logEvent(
            'permission.created',
            Permission::class,
            $permission->id,
            ['name' => $permission->name, 'slug' => $permission->slug, 'resource' => $permission->resource, 'action' => $permission->action]
        );

        return $permission;
    }

    /**
     * Update an existing permission
     *
     * @param  string  $permissionId  Permission ID
     * @param  array  $data  Permission data to update
     * @return Permission Updated permission instance
     *
     * @throws PermissionNotFoundException When permission is not found
     * @throws BusinessRuleException When slug exists or permission is system permission
     */
    public function updatePermission(string $permissionId, array $data): Permission
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (! $tenantId) {
            throw new BusinessRuleException('Tenant context is required for this operation.');
        }

        $permission = $this->permissionRepository->find($permissionId);

        if (! $permission) {
            throw new PermissionNotFoundException("Permission with ID {$permissionId} not found.");
        }

        if ($permission->tenant_id !== $tenantId) {
            throw new BusinessRuleException('Permission does not belong to the current tenant.');
        }

        if ($permission->is_system) {
            throw new BusinessRuleException('System permissions cannot be modified.');
        }

        if (isset($data['slug']) && $this->permissionRepository->slugExists($data['slug'], $tenantId, $permissionId)) {
            throw new BusinessRuleException("Permission with slug '{$data['slug']}' already exists.");
        }

        TransactionHelper::execute(function () use ($permission, $data) {
            $updateData = [];

            foreach (self::ALLOWED_UPDATE_FIELDS as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (! empty($updateData)) {
                $this->permissionRepository->update($permission->id, $updateData);
            }
        });

        $this->auditService->logEvent(
            'permission.updated',
            Permission::class,
            $permission->id,
            [
                'updated_fields' => array_keys(
                    array_intersect_key($data, array_flip(self::ALLOWED_UPDATE_FIELDS))
                ),
            ]
        );

        return $permission->fresh();
    }

    /**
     * Delete a permission
     *
     * @param  string  $permissionId  Permission ID
     * @return void
     *
     * @throws PermissionNotFoundException When permission is not found
     * @throws BusinessRuleException When permission is system permission or has assignments
     */
    public function deletePermission(string $permissionId): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (! $tenantId) {
            throw new BusinessRuleException('Tenant context is required for this operation.');
        }

        $permission = $this->permissionRepository->find($permissionId);

        if (! $permission) {
            throw new PermissionNotFoundException("Permission with ID {$permissionId} not found.");
        }

        if ($permission->tenant_id !== $tenantId) {
            throw new BusinessRuleException('Permission does not belong to the current tenant.');
        }

        if ($permission->is_system) {
            throw new BusinessRuleException('System permissions cannot be deleted.');
        }

        $roleCount = $this->permissionRepository->countRoles($permissionId);
        if ($roleCount > 0) {
            throw new BusinessRuleException(
                "Cannot delete permission. It is currently assigned to {$roleCount} role(s)."
            );
        }

        $userCount = $this->permissionRepository->countUsers($permissionId);
        if ($userCount > 0) {
            throw new BusinessRuleException(
                "Cannot delete permission. It is currently assigned to {$userCount} user(s)."
            );
        }

        $permissionName = $permission->name;

        TransactionHelper::execute(function () use ($permissionId) {
            $this->permissionRepository->deleteWithRelationships($permissionId);
        });

        $this->auditService->logEvent(
            'permission.deleted',
            Permission::class,
            $permissionId,
            ['name' => $permissionName]
        );
    }
}
