<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\AttachPermissionsRequest;
use Modules\Auth\Http\Requests\StoreRoleRequest;
use Modules\Auth\Http\Requests\UpdateRoleRequest;
use Modules\Auth\Http\Resources\RoleResource;
use Modules\Auth\Models\Role;
use Modules\Auth\Repositories\RoleRepository;
use Modules\Auth\Services\RoleService;
use Modules\Tenant\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleController
 *
 * Handles CRUD operations for roles and permission management
 */
class RoleController extends ApiController
{
    public function __construct(
        protected RoleService $roleService,
        protected RoleRepository $roleRepository,
        protected TenantContext $tenantContext
    ) {}

    private function getCurrentTenantId(): ?string
    {
        return $this->tenantContext->getCurrentTenantId();
    }

    private function findRoleOrFail(string $id): Role|JsonResponse
    {
        $role = $this->roleRepository->find($id);
        $tenantId = $this->getCurrentTenantId();

        if (! $role || ($tenantId && $role->tenant_id !== $tenantId)) {
            return $this->notFound('Role not found');
        }

        return $role;
    }

    private function validateNotSystemRole(Role $role, string $action): ?JsonResponse
    {
        if ($role->is_system) {
            return $this->error("System roles cannot be {$action}", Response::HTTP_FORBIDDEN);
        }
        return null;
    }

    /**
     * List all roles
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $filters = array_filter([
            'search' => request()->input('search'),
            'is_system' => request()->input('is_system'),
        ], fn($value) => $value !== null && $value !== '');

        $perPage = request()->input('per_page', 15);
        $roles = $this->roleRepository->findWithFilters($filters, $this->getCurrentTenantId(), $perPage);

        return $this->paginated($roles, RoleResource::class);
    }

    /**
     * Create a new role
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $data = $request->validated();
        $role = $this->roleService->createRole($data);

        return $this->created(
            new RoleResource($role->load(['permissions'])),
            'Role created successfully'
        );
    }

    /**
     * Show a specific role
     */
    public function show(string $id): JsonResponse
    {
        $tenantId = $this->getCurrentTenantId();
        $role = $this->roleRepository->findWithPermissions($id);

        if (! $role || ($tenantId && $role->tenant_id !== $tenantId)) {
            return $this->notFound('Role not found');
        }

        $this->authorize('view', $role);
        $role->load(['users']);

        return $this->success(new RoleResource($role));
    }

    /**
     * Update a role
     */
    public function update(UpdateRoleRequest $request, string $id): JsonResponse
    {
        $role = $this->findRoleOrFail($id);
        if ($role instanceof JsonResponse) return $role;

        $this->authorize('update', $role);
        if ($error = $this->validateNotSystemRole($role, 'modified')) return $error;

        $updatedRole = $this->roleService->updateRole($id, $request->validated());

        return $this->success(
            new RoleResource($updatedRole->fresh(['permissions'])),
            'Role updated successfully'
        );
    }

    /**
     * Delete a role
     */
    public function destroy(string $id): JsonResponse
    {
        $role = $this->findRoleOrFail($id);
        if ($role instanceof JsonResponse) return $role;

        $this->authorize('delete', $role);
        if ($error = $this->validateNotSystemRole($role, 'deleted')) return $error;

        $this->roleService->deleteRole($id);

        return $this->success(null, 'Role deleted successfully');
    }

    /**
     * Attach permissions to a role
     */
    public function attachPermissions(AttachPermissionsRequest $request, string $id): JsonResponse
    {
        $role = $this->findRoleOrFail($id);
        if ($role instanceof JsonResponse) return $role;

        $this->authorize('managePermissions', $role);
        if ($error = $this->validateNotSystemRole($role, 'modified')) return $error;

        $this->roleRepository->attachPermissions($id, $request->input('permission_ids'));

        return $this->success(
            new RoleResource($role->fresh(['permissions'])),
            'Permissions attached successfully'
        );
    }

    /**
     * Detach permissions from a role
     */
    public function detachPermissions(AttachPermissionsRequest $request, string $id): JsonResponse
    {
        $role = $this->findRoleOrFail($id);
        if ($role instanceof JsonResponse) return $role;

        $this->authorize('managePermissions', $role);
        if ($error = $this->validateNotSystemRole($role, 'modified')) return $error;

        $this->roleRepository->detachPermissions($id, $request->input('permission_ids'));

        return $this->success(
            new RoleResource($role->fresh(['permissions'])),
            'Permissions detached successfully'
        );
    }

    /**
     * Sync permissions to a role (replace all)
     */
    public function syncPermissions(AttachPermissionsRequest $request, string $id): JsonResponse
    {
        $role = $this->findRoleOrFail($id);
        if ($role instanceof JsonResponse) return $role;

        $this->authorize('managePermissions', $role);
        if ($error = $this->validateNotSystemRole($role, 'modified')) return $error;

        $updatedRole = $this->roleService->syncPermissions($id, $request->input('permission_ids'));

        return $this->success(new RoleResource($updatedRole), 'Permissions synced successfully');
    }
}
