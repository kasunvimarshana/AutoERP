<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Application\Contracts\UseCases\UserRoleServiceInterface;
use Modules\User\Presentation\Http\Requests\ListUserEntityRequest;
use Modules\User\Presentation\Http\Requests\UpsertUserRoleRequest;
use Modules\User\Presentation\Http\Resources\UserRecordResource;

final class UserRoleController extends AbstractUserCrudController
{
    public function __construct(private readonly UserRoleServiceInterface $service)
    {
    }

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $userRole): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->service->get($userRole));
    }

    public function store(UpsertUserRoleRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->service->create($request->validated()));
    }

    public function update(UpsertUserRoleRequest $request, int|string $userRole): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->update($userRole, $request->validated()));
    }

    public function destroy(int|string $userRole): JsonResponse
    {
        return $this->responseForDelete($this->service->delete($userRole));
    }
}
