<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;
use Modules\User\Application\Contracts\DeleteRoleServiceInterface;
use Modules\User\Application\Contracts\FindRoleServiceInterface;
use Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\User\Domain\Entities\Role;
use Modules\User\Infrastructure\Http\Requests\ListRoleRequest;
use Modules\User\Infrastructure\Http\Requests\StoreRoleRequest;
use Modules\User\Infrastructure\Http\Requests\SyncRolePermissionsRequest;
use Modules\User\Infrastructure\Http\Resources\RoleCollection;
use Modules\User\Infrastructure\Http\Resources\RoleResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoleController extends AuthorizedController
{
    public function __construct(
        protected FindRoleServiceInterface $findRoleService,
        protected CreateRoleServiceInterface $createRoleService,
        protected DeleteRoleServiceInterface $deleteRoleService,
        protected SyncRolePermissionsServiceInterface $syncPermissionsService
    ) {}

    public function index(ListRoleRequest $request): RoleCollection
    {
        $this->authorize('viewAny', Role::class);
        $validated = $request->validated();
        $filters = [];
        if (array_key_exists('tenant_id', $validated)) {
            $filters['tenant_id'] = (int) $validated['tenant_id'];
        }
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $roles = $this->findRoleService->list($filters, $perPage, $page);

        return new RoleCollection($roles);
    }

    public function show(int $roleId): RoleResource
    {
        $roleEntity = $this->findRoleOrFail($roleId);
        $this->authorize('view', $roleEntity);

        return new RoleResource($roleEntity);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);
        $role = $this->createRoleService->execute($request->validated());

        return (new RoleResource($role))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function destroy(int $roleId): JsonResponse
    {
        $roleEntity = $this->findRoleOrFail($roleId);
        $this->authorize('delete', $roleEntity);
        $this->deleteRoleService->execute(['id' => $roleId]);

        return Response::json(['message' => 'Role deleted successfully']);
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, int $roleId): RoleResource
    {
        $roleEntity = $this->findRoleOrFail($roleId);
        $this->authorize('syncPermissions', $roleEntity);
        $validated = $request->validated();
        $updated = $this->syncPermissionsService->execute([
            'role_id' => $roleId,
            'permission_ids' => $validated['permission_ids'],
        ]);

        return new RoleResource($updated);
    }

    private function findRoleOrFail(int $roleId): Role
    {
        $role = $this->findRoleService->find($roleId);
        if (! $role) {
            throw new NotFoundHttpException('Role not found.');
        }

        return $role;
    }
}
