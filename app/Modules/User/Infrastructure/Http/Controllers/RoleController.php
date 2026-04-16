<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;
use Modules\User\Application\Contracts\DeleteRoleServiceInterface;
use Modules\User\Application\Contracts\FindRoleServiceInterface;
use Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\User\Domain\Entities\Role;
use Modules\User\Infrastructure\Http\Requests\StoreRoleRequest;
use Modules\User\Infrastructure\Http\Requests\SyncRolePermissionsRequest;
use Modules\User\Infrastructure\Http\Resources\RoleResource;
use OpenApi\Attributes as OA;

class RoleController extends AuthorizedController
{
    public function __construct(
        protected FindRoleServiceInterface $findService,
        protected CreateRoleServiceInterface $createService,
        protected DeleteRoleServiceInterface $deleteService,
        protected SyncRolePermissionsServiceInterface $syncPermissionsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);
        $filters = [];
        if ($tenantId = $request->query('tenant_id')) {
            $filters['tenant_id'] = (int) $tenantId;
        }
        $perPage = (int) $request->input('per_page', 15);
        $page    = (int) $request->input('page', 1);
        $roles   = $this->findService->list($filters, $perPage, $page);

        return response()->json(RoleResource::collection($roles));
    }

    public function show(int $id): RoleResource
    {
        $role = $this->findService->find($id);
        if (! $role) {
            abort(404);
        }
        $this->authorize('view', $role);

        return new RoleResource($role);
    }

    public function store(StoreRoleRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', Role::class);
        $role = $this->createService->execute($request->validated());

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    public function destroy(int $id): JsonResponse
    {
        $role = $this->findService->find($id);
        if (! $role) {
            abort(404);
        }
        $this->authorize('delete', $role);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Role deleted successfully']);
    }

    
    public function syncPermissions(SyncRolePermissionsRequest $request, int $id): RoleResource
    {
        $role = $this->findService->find($id);
        if (! $role) {
            abort(404);
        }
        $this->authorize('syncPermissions', $role);
        $updated = $this->syncPermissionsService->execute([
            'role_id'        => $id,
            'permission_ids' => $request->validated()['permission_ids'],
        ]);

        return new RoleResource($updated);
    }
}
