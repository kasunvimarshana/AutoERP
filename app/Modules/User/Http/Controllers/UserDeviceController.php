<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\ListUserEntityRequest;
use Modules\User\Http\Requests\UpsertUserDeviceRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\UserDeviceService;

final class UserDeviceController extends AbstractUserCrudController
{
    public function __construct(private readonly UserDeviceService $service) {}

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $userDevice): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->service->get($userDevice));
    }

    public function store(UpsertUserDeviceRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->service->create($request->validated()));
    }

    public function update(UpsertUserDeviceRequest $request, int|string $userDevice): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->update($userDevice, $request->validated()));
    }

    public function destroy(int|string $userDevice): JsonResponse
    {
        return $this->responseForDelete($this->service->delete($userDevice));
    }
}
