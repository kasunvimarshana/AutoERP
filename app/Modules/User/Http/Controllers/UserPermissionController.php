<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\ListUserEntityRequest;
use Modules\User\Http\Requests\UpsertUserPermissionRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\UserPermissionService;

final class UserPermissionController extends AbstractUserCrudController
{
    public function __construct(private readonly UserPermissionService $service) {}

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $userPermission): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->service->get($userPermission));
    }

    public function store(UpsertUserPermissionRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->service->create($request->validated()));
    }

    public function update(UpsertUserPermissionRequest $request, int|string $userPermission): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->update($userPermission, $request->validated()));
    }

    public function destroy(int|string $userPermission): JsonResponse
    {
        return $this->responseForDelete($this->service->delete($userPermission));
    }
}
