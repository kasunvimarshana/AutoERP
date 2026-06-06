<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\ListUserEntityRequest;
use Modules\User\Http\Requests\UpsertRolePermissionRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\RolePermissionService;

final class RolePermissionController extends AbstractUserCrudController
{
    public function __construct(private readonly RolePermissionService $service) {}

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $rolePermission): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->service->get($rolePermission));
    }

    public function store(UpsertRolePermissionRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->service->create($request->validated()));
    }

    public function update(UpsertRolePermissionRequest $request, int|string $rolePermission): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->update($rolePermission, $request->validated()));
    }

    public function destroy(int|string $rolePermission): JsonResponse
    {
        return $this->responseForDelete($this->service->delete($rolePermission));
    }
}
