<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\ListUserEntityRequest;
use Modules\User\Http\Requests\UpsertRoleRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\RoleService;

final class RoleController extends AbstractUserCrudController
{
    public function __construct(private readonly RoleService $service) {}

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $role): JsonResponse|UserRecordResource
    {
        if (! $this->canUse(UserPermission::ROLES_VIEW)) {
            return $this->forbidden();
        }

        return $this->responseForShow($this->service->get($role));
    }

    public function store(UpsertRoleRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->service->create($request->validated()));
    }

    public function update(UpsertRoleRequest $request, int|string $role): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->update($role, $request->validated()));
    }

    public function destroy(int|string $role): JsonResponse
    {
        if (! $this->canUse(UserPermission::ROLES_DELETE)) {
            return $this->forbidden();
        }

        return $this->responseForDelete($this->service->delete($role));
    }
}
