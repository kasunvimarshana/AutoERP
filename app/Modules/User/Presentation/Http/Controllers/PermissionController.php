<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Application\Contracts\UseCases\PermissionServiceInterface;
use Modules\User\Presentation\Http\Requests\ListUserEntityRequest;
use Modules\User\Presentation\Http\Requests\UpsertPermissionRequest;
use Modules\User\Presentation\Http\Resources\UserRecordResource;

final class PermissionController extends AbstractUserCrudController
{
    public function __construct(private readonly PermissionServiceInterface $service)
    {
    }

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $permission): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->service->get($permission));
    }

    public function store(UpsertPermissionRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->service->create($request->validated()));
    }

    public function update(UpsertPermissionRequest $request, int|string $permission): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->update($permission, $request->validated()));
    }

    public function destroy(int|string $permission): JsonResponse
    {
        return $this->responseForDelete($this->service->delete($permission));
    }
}
