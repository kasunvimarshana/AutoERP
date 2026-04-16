<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\CreatePermissionServiceInterface;
use Modules\User\Application\Contracts\DeletePermissionServiceInterface;
use Modules\User\Application\Contracts\FindPermissionServiceInterface;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Infrastructure\Http\Requests\StorePermissionRequest;
use Modules\User\Infrastructure\Http\Resources\PermissionResource;
use OpenApi\Attributes as OA;

class PermissionController extends AuthorizedController
{
    public function __construct(
        protected FindPermissionServiceInterface $findService,
        protected CreatePermissionServiceInterface $createService,
        protected DeletePermissionServiceInterface $deleteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);
        $filters = [];
        if ($tenantId = $request->query('tenant_id')) {
            $filters['tenant_id'] = (int) $tenantId;
        }
        $perPage     = (int) $request->input('per_page', 15);
        $page        = (int) $request->input('page', 1);
        $permissions = $this->findService->list($filters, $perPage, $page);

        return response()->json(PermissionResource::collection($permissions));
    }

    public function show(int $id): PermissionResource
    {
        $permission = $this->findService->find($id);
        if (! $permission) {
            abort(404);
        }
        $this->authorize('view', $permission);

        return new PermissionResource($permission);
    }

    public function store(StorePermissionRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', Permission::class);
        $permission = $this->createService->execute($request->validated());

        return (new PermissionResource($permission))->response()->setStatusCode(201);
    }


    public function destroy(int $id): JsonResponse
    {
        $permission = $this->findService->find($id);
        if (! $permission) {
            abort(404);
        }
        $this->authorize('delete', $permission);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
