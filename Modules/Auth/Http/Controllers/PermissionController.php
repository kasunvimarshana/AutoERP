<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\StorePermissionRequest;
use Modules\Auth\Http\Requests\UpdatePermissionRequest;
use Modules\Auth\Http\Resources\PermissionResource;
use Modules\Auth\Models\Permission;
use Modules\Auth\Repositories\PermissionRepository;
use Modules\Auth\Services\PermissionService;
use Modules\Tenant\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * PermissionController
 *
 * Handles CRUD operations for permissions
 */
class PermissionController extends ApiController
{
    public function __construct(
        protected PermissionService $permissionService,
        protected PermissionRepository $permissionRepository,
        protected TenantContext $tenantContext
    ) {}

    private function getCurrentTenantId(): ?string
    {
        return $this->tenantContext->hasTenant() 
            ? $this->tenantContext->getCurrentTenantId() 
            : null;
    }

    private function findPermissionOrFail(string $id): Permission|JsonResponse
    {
        $permission = $this->permissionRepository->find($id);
        $tenantId = $this->getCurrentTenantId();

        if (! $permission || ($tenantId && $permission->tenant_id !== $tenantId)) {
            return $this->notFound('Permission not found');
        }

        return $permission;
    }

    private function validateNotSystemPermission(Permission $permission, string $action): ?JsonResponse
    {
        if ($permission->is_system) {
            return $this->error("System permissions cannot be {$action}", Response::HTTP_FORBIDDEN);
        }
        return null;
    }

    /**
     * List all permissions
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $filters = array_filter([
            'search' => request()->input('search'),
            'resource' => request()->input('resource'),
            'action' => request()->input('action'),
            'is_system' => request()->input('is_system'),
        ], fn($value) => $value !== null && $value !== '');

        $perPage = request()->input('per_page', 15);
        $permissions = $this->permissionRepository->findWithFilters($filters, $this->getCurrentTenantId(), $perPage);

        return $this->paginated($permissions, PermissionResource::class);
    }

    /**
     * Create a new permission
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $this->authorize('create', Permission::class);

        $data = $request->validated();
        $permission = $this->permissionService->createPermission($data);

        return $this->created(
            new PermissionResource($permission),
            'Permission created successfully'
        );
    }

    /**
     * Show a specific permission
     */
    public function show(string $id): JsonResponse
    {
        $tenantId = $this->getCurrentTenantId();
        $permission = $this->permissionRepository->findWithRoles($id);

        if (! $permission || ($tenantId && $permission->tenant_id !== $tenantId)) {
            return $this->notFound('Permission not found');
        }

        $this->authorize('view', $permission);

        return $this->success(new PermissionResource($permission));
    }

    /**
     * Update a permission
     */
    public function update(UpdatePermissionRequest $request, string $id): JsonResponse
    {
        $permission = $this->findPermissionOrFail($id);
        if ($permission instanceof JsonResponse) return $permission;

        $this->authorize('update', $permission);
        if ($error = $this->validateNotSystemPermission($permission, 'modified')) return $error;

        $updatedPermission = $this->permissionService->updatePermission($id, $request->validated());

        return $this->success(
            new PermissionResource($updatedPermission->fresh(['roles'])),
            'Permission updated successfully'
        );
    }

    /**
     * Delete a permission
     */
    public function destroy(string $id): JsonResponse
    {
        $permission = $this->findPermissionOrFail($id);
        if ($permission instanceof JsonResponse) return $permission;

        $this->authorize('delete', $permission);
        if ($error = $this->validateNotSystemPermission($permission, 'deleted')) return $error;

        $this->permissionService->deletePermission($id);

        return $this->success(null, 'Permission deleted successfully');
    }
}
