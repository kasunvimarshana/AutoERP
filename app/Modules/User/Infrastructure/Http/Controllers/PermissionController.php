<?php

namespace Modules\User\Infrastructure\Http\Controllers;

use Modules\User\Application\Contracts\CreatePermissionServiceInterface;
use Modules\User\Application\Contracts\DeletePermissionServiceInterface;
use Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\User\Infrastructure\Http\Requests\StorePermissionRequest;
use Modules\User\Infrastructure\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PermissionController extends Controller
{
    public function __construct(
        protected CreatePermissionServiceInterface $createService,
        protected DeletePermissionServiceInterface $deleteService,
        protected PermissionRepositoryInterface $permissionRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\User\Domain\Entities\Permission::class);
        $repo = clone $this->permissionRepository;
        if ($tenantId = $request->query('tenant_id')) {
            $repo->where('tenant_id', (int) $tenantId);
        }
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);
        $permissions = $repo->paginate($perPage, ['*'], 'page', $page);
        return response()->json(PermissionResource::collection($permissions));
    }

    public function show(int $id): PermissionResource
    {
        $permission = $this->permissionRepository->find($id);
        if (!$permission) {
            abort(404);
        }
        $this->authorize('view', $permission);
        return new PermissionResource($permission);
    }

    public function store(StorePermissionRequest $request): PermissionResource
    {
        $this->authorize('create', \Modules\User\Domain\Entities\Permission::class);
        $permission = $this->createService->execute($request->validated());
        return new PermissionResource($permission);
    }

    public function destroy(int $id): JsonResponse
    {
        $permission = $this->permissionRepository->find($id);
        if (!$permission) {
            abort(404);
        }
        $this->authorize('delete', $permission);
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
