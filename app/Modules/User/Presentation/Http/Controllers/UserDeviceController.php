<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\DTOs\UserDeviceData;
use Modules\User\Application\Services\UserService;
use Modules\User\Domain\Exceptions\UserRecordNotFoundException;
use Modules\User\Presentation\Http\Controllers\Concerns\HandlesUserHttp;
use Modules\User\Presentation\Http\Requests\StoreUserDeviceRequest;
use Modules\User\Presentation\Http\Requests\UpdateUserDeviceRequest;
use Modules\User\Presentation\Http\Resources\UserDeviceResource;

class UserDeviceController extends Controller
{
    use HandlesUserHttp;

    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): mixed
    {
        return UserDeviceResource::collection($this->users->listUserDevices(
            $this->filters($request, ['tenant_id', 'organization_unit_id', 'user_id', 'device_token', 'platform']),
            $this->perPage($request),
        ));
    }

    public function store(StoreUserDeviceRequest $request): UserDeviceResource
    {
        $record = $this->users->createUserDevice(UserDeviceData::fromArray($request->validated()));

        return new UserDeviceResource($record);
    }

    public function show(int|string $user_device): UserDeviceResource|JsonResponse
    {
        try {
            return new UserDeviceResource($this->users->findUserDevice($user_device));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateUserDeviceRequest $request, int|string $user_device): UserDeviceResource|JsonResponse
    {
        try {
            return new UserDeviceResource($this->users->updateUserDevice($user_device, UserDeviceData::fromArray($request->validated())));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $user_device): JsonResponse
    {
        try {
            $this->users->deleteUserDevice($user_device);

            return response()->json(null, 204);
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
