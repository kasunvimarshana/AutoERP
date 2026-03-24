<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;
use Modules\User\Application\Contracts\DeleteRoleServiceInterface;
use Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Infrastructure\Http\Requests\StoreRoleRequest;
use Modules\User\Infrastructure\Http\Requests\SyncRolePermissionsRequest;
use Modules\User\Infrastructure\Http\Resources\RoleResource;

class RoleController extends Controller
{
    public function __construct(
        protected CreateRoleServiceInterface $createService,
        protected DeleteRoleServiceInterface $deleteService,
        protected SyncRolePermissionsServiceInterface $syncPermissionsService,
        protected RoleRepositoryInterface $roleRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);
        $repo = clone $this->roleRepository;
        if ($tenantId = $request->query('tenant_id')) {
            $repo->where('tenant_id', (int) $tenantId);
        }
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);
        $roles = $repo->paginate($perPage, ['*'], 'page', $page);

        return response()->json(RoleResource::collection($roles));
    }

    public function show(int $id): RoleResource
    {
        $role = $this->roleRepository->find($id);
        if (! $role) {
            abort(404);
        }
        $this->authorize('view', $role);

        return new RoleResource($role);
    }

    public function store(StoreRoleRequest $request): RoleResource
    {
        $this->authorize('create', Role::class);
        $role = $this->createService->execute($request->validated());

        return new RoleResource($role);
    }

    public function destroy(int $id): JsonResponse
    {
        $role = $this->roleRepository->find($id);
        if (! $role) {
            abort(404);
        }
        $this->authorize('delete', $role);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, int $id): RoleResource
    {
        $role = $this->roleRepository->find($id);
        if (! $role) {
            abort(404);
        }
        $this->authorize('syncPermissions', $role);
        $updated = $this->syncPermissionsService->execute([
            'role_id' => $id,
            'permission_ids' => $request->validated()['permission_ids'],
        ]);

        return new RoleResource($updated);
    }
}
