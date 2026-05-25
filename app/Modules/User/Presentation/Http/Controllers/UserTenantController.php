<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Application\Contracts\UseCases\UserTenantServiceInterface;
use Modules\User\Presentation\Http\Requests\ListUserEntityRequest;
use Modules\User\Presentation\Http\Requests\UpsertUserTenantRequest;
use Modules\User\Presentation\Http\Resources\UserRecordResource;

final class UserTenantController extends AbstractUserCrudController
{
    public function __construct(private readonly UserTenantServiceInterface $service)
    {
    }

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
