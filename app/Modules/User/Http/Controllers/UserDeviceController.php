<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\Devices\ListUserDevicesRequest;
use Modules\User\Http\Requests\Devices\RegisterUserDeviceRequest;
use Modules\User\Http\Requests\Devices\VersionedUserDeviceRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\UserDeviceService;

final class UserDeviceController extends AbstractUserCrudController
{
    public function __construct(private readonly UserDeviceService $devices) {}

    public function index(ListUserDevicesRequest $request, int|string $user): JsonResponse
    {
        return $this->responseForList($this->devices->list($user, $request->validated()));
    }

    public function store(RegisterUserDeviceRequest $request, int|string $user): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->devices->register($user, $request->validated()));
    }

    public function touch(
        VersionedUserDeviceRequest $request,
        int|string $user,
        int|string $device,
    ): JsonResponse|UserRecordResource {
        return $this->responseForUpdate($this->devices->touch(
            $user,
            $device,
            (int) $request->validated('expected_version'),
        ));
    }

    public function revoke(
        VersionedUserDeviceRequest $request,
        int|string $user,
        int|string $device,
    ): JsonResponse|UserRecordResource {
        return $this->responseForUpdate($this->devices->revoke(
            $user,
            $device,
            (int) $request->validated('expected_version'),
        ));
    }
}
