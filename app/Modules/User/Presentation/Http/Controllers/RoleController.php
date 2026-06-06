<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Application\UseCases\RoleService;
use Modules\User\Presentation\Http\Requests\ListUserEntityRequest;
use Modules\User\Presentation\Http\Requests\UpsertRoleRequest;
use Modules\User\Presentation\Http\Resources\UserRecordResource;

final class RoleController extends AbstractUserCrudController
{
    public function __construct(private readonly RoleService $service) {}

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $role): JsonResponse|UserRecordResource
    {
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
        return $this->responseForDelete($this->service->delete($role));
    }
}
