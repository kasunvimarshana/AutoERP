<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\Roles\CreateRoleRequest;
use Modules\User\Http\Requests\Roles\DeleteRoleRequest;
use Modules\User\Http\Requests\Roles\ListRolesRequest;
use Modules\User\Http\Requests\Roles\SyncRolePermissionsRequest;
use Modules\User\Http\Requests\Roles\UpdateRoleRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\RolePermissionService;
use Modules\User\Services\RoleService;

final class RoleController extends AbstractUserCrudController
{
    public function __construct(
        private readonly RoleService $roles,
        private readonly RolePermissionService $permissions,
    ) {}

    public function index(ListRolesRequest $request): JsonResponse
    {
        return $this->responseForList($this->roles->list($request->validated()));
    }

    public function show(int|string $role): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->roles->get($role));
    }

    public function store(CreateRoleRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->roles->create($request->validated()));
    }

    public function update(UpdateRoleRequest $request, int|string $role): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->roles->update($role, $request->validated()));
    }

    public function destroy(DeleteRoleRequest $request, int|string $role): JsonResponse
    {
        return $this->responseForDelete($this->roles->delete(
            $role,
            (int) $request->validated('expected_version'),
        ));
    }

    public function syncPermissions(
        SyncRolePermissionsRequest $request,
        int|string $role,
    ): JsonResponse|UserRecordResource {
        $payload = $request->validated();

        return $this->responseForUpdate($this->permissions->sync(
            $role,
            (int) $payload['expected_version'],
            $payload['permission_ids'],
        ));
    }
}
