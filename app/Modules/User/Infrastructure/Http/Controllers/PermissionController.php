<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\CreatePermissionServiceInterface;
use Modules\User\Application\Contracts\DeletePermissionServiceInterface;
use Modules\User\Application\Contracts\FindPermissionServiceInterface;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Infrastructure\Http\Requests\ListPermissionRequest;
use Modules\User\Infrastructure\Http\Requests\StorePermissionRequest;
use Modules\User\Infrastructure\Http\Resources\PermissionCollection;
use Modules\User\Infrastructure\Http\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PermissionController extends AuthorizedController
{
    public function __construct(
        protected FindPermissionServiceInterface $findPermissionService,
        protected CreatePermissionServiceInterface $createPermissionService,
        protected DeletePermissionServiceInterface $deletePermissionService
    ) {}

    public function index(ListPermissionRequest $request): PermissionCollection
    {
        $this->authorize('viewAny', Permission::class);
        $validated = $request->validated();
        $filters = [];
        if (array_key_exists('tenant_id', $validated)) {
            $filters['tenant_id'] = (int) $validated['tenant_id'];
        }
        $perPage     = (int) ($validated['per_page'] ?? 15);
        $page        = (int) ($validated['page'] ?? 1);
        $permissions = $this->findPermissionService->list($filters, $perPage, $page);

        return new PermissionCollection($permissions);
    }

    public function show(int $permissionId): PermissionResource
    {
        $permissionEntity = $this->findPermissionOrFail($permissionId);
        $this->authorize('view', $permissionEntity);

        return new PermissionResource($permissionEntity);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $this->authorize('create', Permission::class);
        $permission = $this->createPermissionService->execute($request->validated());

        return (new PermissionResource($permission))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function destroy(int $permissionId): JsonResponse
    {
        $permissionEntity = $this->findPermissionOrFail($permissionId);
        $this->authorize('delete', $permissionEntity);
        $this->deletePermissionService->execute(['id' => $permissionId]);

        return Response::json(['message' => 'Permission deleted successfully']);
    }

    private function findPermissionOrFail(int $permissionId): Permission
    {
        $permission = $this->findPermissionService->find($permissionId);
        if (! $permission) {
            throw new NotFoundHttpException('Permission not found.');
        }

        return $permission;
    }
}
