<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\DeleteUserDeviceServiceInterface;
use Modules\User\Application\Contracts\FindUserDevicesServiceInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Application\Contracts\UpsertUserDeviceServiceInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Infrastructure\Http\Requests\ListUserDeviceRequest;
use Modules\User\Infrastructure\Http\Requests\UpsertUserDeviceRequest;
use Modules\User\Infrastructure\Http\Resources\UserDeviceCollection;
use Modules\User\Infrastructure\Http\Resources\UserDeviceResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserDeviceController extends AuthorizedController
{
    public function __construct(
        protected FindUserServiceInterface $findUserService,
        protected FindUserDevicesServiceInterface $findUserDevicesService,
        protected UpsertUserDeviceServiceInterface $upsertUserDeviceService,
        protected DeleteUserDeviceServiceInterface $deleteUserDeviceService
    ) {}

    public function index(int $userId, ListUserDeviceRequest $request): UserDeviceCollection
    {
        $userEntity = $this->findUserOrFail($userId);
        $this->authorize('view', $userEntity);

        $validated = $request->validated();
        $platform = $validated['platform'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $devices = $this->findUserDevicesService->paginateByUser(
            $userId,
            is_string($platform) ? $platform : null,
            $perPage,
            $page
        );

        return new UserDeviceCollection($devices);
    }

    public function store(UpsertUserDeviceRequest $request, int $userId): UserDeviceResource
    {
        $userEntity = $this->findUserOrFail($userId);
        $this->authorize('update', $userEntity);

        $device = $this->upsertUserDeviceService->execute([
            'user_id' => $userId,
            ...$request->validated(),
        ]);

        return new UserDeviceResource($device);
    }

    public function destroy(int $userId, int $deviceId): JsonResponse
    {
        $userEntity = $this->findUserOrFail($userId);
        $this->authorize('update', $userEntity);

        $this->deleteUserDeviceService->execute([
            'user_id' => $userId,
            'device_id' => $deviceId,
        ]);

        return Response::json(['message' => 'Device deleted successfully']);
    }

    private function findUserOrFail(int $userId): User
    {
        $user = $this->findUserService->find($userId);
        if (! $user) {
            throw new NotFoundHttpException('User not found.');
        }

        return $user;
    }
}
