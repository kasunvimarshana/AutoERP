<?php

namespace App\Modules\IAM\Services;

use App\Modules\IAM\Models\Permission;
use App\Modules\IAM\Repositories\PermissionRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Permission Service
 *
 * Handles business logic for permission management.
 * Note: Permissions are system-defined and not created via API.
 */
class PermissionService extends BaseService
{
    public function __construct(
        protected PermissionRepository $permissionRepository
    ) {}

    /**
     * Get all permissions
     */
    public function getAllPermissions(): Collection
    {
        return $this->permissionRepository->all();
    }

    /**
     * Get paginated permissions
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedPermissions(int $perPage = 15)
    {
        return $this->permissionRepository->paginate($perPage);
    }

    /**
     * Get permission by ID
     */
    public function getPermissionById(int $id): Permission
    {
        return $this->permissionRepository->findOrFail($id);
    }

    /**
     * Get permission by slug
     */
    public function getPermissionBySlug(string $slug): ?Permission
    {
        return $this->permissionRepository->findBySlug($slug);
    }

    /**
     * Get permissions by module
     */
    public function getPermissionsByModule(string $module): Collection
    {
        return $this->permissionRepository->getByModule($module);
    }

    /**
     * Get all permissions grouped by module
     */
    public function getPermissionsGroupedByModule(): Collection
    {
        return $this->permissionRepository->getAllGroupedByModule();
    }

    /**
     * Get permission roles
     */
    public function getPermissionRoles(int $permissionId): Collection
    {
        return $this->permissionRepository->getPermissionRoles($permissionId);
    }
}
