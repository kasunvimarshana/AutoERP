<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\ListUserEntityRequest;
use Modules\User\Http\Requests\UpsertUserTenantRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\UserTenantService;

final class UserTenantController extends AbstractUserCrudController
{
    public function __construct(private readonly UserTenantService $service) {}

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $userTenant): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->service->get($userTenant));
    }

    public function store(UpsertUserTenantRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->service->create($request->validated()));
    }

    public function update(UpsertUserTenantRequest $request, int|string $userTenant): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->update($userTenant, $request->validated()));
    }

    public function destroy(int|string $userTenant): JsonResponse
    {
        return $this->responseForDelete($this->service->delete($userTenant));
    }
}
