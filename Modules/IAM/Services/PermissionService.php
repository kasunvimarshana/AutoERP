<?php

declare(strict_types=1);

namespace Modules\IAM\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\IAM\Entities\Permission;
use Modules\IAM\Events\PermissionCreated;
use Modules\IAM\Events\PermissionDeleted;
use Modules\IAM\Events\PermissionUpdated;
use Modules\IAM\Repositories\Contracts\PermissionRepositoryInterface;

/**
 * Permission Service
 *
 * Handles business logic for permission management.
 * Uses native Laravel features only.
 */
class PermissionService
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository
    ) {}

    /**
     * Create a new permission.
     */
    public function createPermission(array $data): Permission
    {
        DB::beginTransaction();
        try {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Validate slug uniqueness
            if ($this->permissionRepository->findBySlug($data['slug'])) {
                throw new \Exception("Permission with slug '{$data['slug']}' already exists");
            }

            $permission = $this->permissionRepository->create($data);

            event(new PermissionCreated($permission));

            DB::commit();

            return $permission;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a permission.
     */
    public function updatePermission(int $id, array $data): Permission
    {
        DB::beginTransaction();
        try {
            $permission = $this->permissionRepository->findById($id);

            if (! $permission) {
                throw new \Exception("Permission not found: {$id}");
            }

            // Check slug uniqueness if changed
            if (isset($data['slug']) && $data['slug'] !== $permission->slug) {
                $existing = $this->permissionRepository->findBySlug($data['slug']);
                if ($existing && $existing->id !== $permission->id) {
                    throw new \Exception("Permission with slug '{$data['slug']}' already exists");
                }
            }

            $permission = $this->permissionRepository->update($permission, $data);

            event(new PermissionUpdated($permission));

            DB::commit();

            return $permission;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a permission.
     */
    public function deletePermission(int $id): bool
    {
        DB::beginTransaction();
        try {
            $permission = $this->permissionRepository->findById($id);

            if (! $permission) {
                throw new \Exception("Permission not found: {$id}");
            }

            $result = $this->permissionRepository->delete($permission);

            event(new PermissionDeleted($permission));

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign permissions to a role.
     */
    public function assignPermissionsToRole(int $roleId, array $permissionIds): void
    {
        DB::beginTransaction();
        try {
            // Validate all permissions exist
            foreach ($permissionIds as $permissionId) {
                if (! $this->permissionRepository->findById($permissionId)) {
                    throw new \Exception("Permission not found: {$permissionId}");
                }
            }

            $this->permissionRepository->syncToRole($roleId, $permissionIds);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign permissions directly to a user.
     */
    public function assignPermissionsToUser(int $userId, array $permissionIds, string $type = 'grant'): void
    {
        DB::beginTransaction();
        try {
            // Validate type
            if (! in_array($type, ['grant', 'deny'])) {
                throw new \Exception("Invalid permission type. Must be 'grant' or 'deny'");
            }

            // Validate all permissions exist
            foreach ($permissionIds as $permissionId) {
                if (! $this->permissionRepository->findById($permissionId)) {
                    throw new \Exception("Permission not found: {$permissionId}");
                }
            }

            $this->permissionRepository->syncToUser($userId, $permissionIds, $type);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create permissions in bulk from array.
     */
    public function createBulkPermissions(array $permissions): array
    {
        DB::beginTransaction();
        try {
            $created = [];

            foreach ($permissions as $permissionData) {
                $permission = $this->createPermission($permissionData);
                $created[] = $permission;
            }

            DB::commit();

            return $created;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate standard CRUD permissions for a resource.
     */
    public function generateCrudPermissions(string $module, string $resource): array
    {
        $actions = ['view', 'create', 'update', 'delete'];
        $permissions = [];

        foreach ($actions as $action) {
            $slug = "{$module}.{$resource}.{$action}";
            $name = ucfirst($action).' '.ucfirst($resource);

            $permissions[] = [
                'name' => $name,
                'slug' => $slug,
                'module' => $module,
                'resource' => $resource,
                'action' => $action,
                'description' => "Permission to {$action} {$resource} in {$module} module",
                'is_active' => true,
            ];
        }

        return $this->createBulkPermissions($permissions);
    }
}
