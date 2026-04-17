<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Auth\Application\UseCases\GetAuthenticatedUser;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Contracts\DeleteUserDeviceServiceInterface;
use Modules\User\Application\Contracts\FindUserDevicesServiceInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Application\Contracts\UpsertUserDeviceServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Infrastructure\Http\Requests\ChangePasswordRequest;
use Modules\User\Infrastructure\Http\Requests\ListUserDeviceRequest;
use Modules\User\Infrastructure\Http\Requests\UpsertUserDeviceRequest;
use Modules\User\Infrastructure\Http\Requests\UpdatePreferencesRequest;
use Modules\User\Infrastructure\Http\Requests\UpdateProfileRequest;
use Modules\User\Infrastructure\Http\Requests\UploadAvatarRequest;
use Modules\User\Infrastructure\Http\Resources\UserDeviceCollection;
use Modules\User\Infrastructure\Http\Resources\UserDeviceResource;
use Modules\User\Infrastructure\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ProfileController extends AuthorizedController
{
    public function __construct(
        protected GetAuthenticatedUser $getAuthenticatedUser,
        protected FindUserServiceInterface $findUserService,
        protected FindUserDevicesServiceInterface $findUserDevicesService,
        protected UpsertUserDeviceServiceInterface $upsertUserDeviceService,
        protected DeleteUserDeviceServiceInterface $deleteUserDeviceService,
        protected UpdateProfileServiceInterface $updateProfileService,
        protected ChangePasswordServiceInterface $changePasswordService,
        protected UpdatePreferencesServiceInterface $updatePreferencesService,
        protected UploadAvatarServiceInterface $uploadAvatarService
    ) {}

    public function show(): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return Response::json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $user = $this->findUserService->find($userId);

        if (! $user) {
            return Response::json(['message' => 'User profile unavailable'], HttpResponse::HTTP_NOT_FOUND);
        }

        return Response::json(new UserResource($user));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return Response::json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $updatePayload = array_merge($request->validated(), ['user_id' => $userId]);
        $user = $this->updateProfileService->execute($updatePayload);

        return Response::json(new UserResource($user));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return Response::json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $passwordChangePayload = array_merge($request->validated(), ['user_id' => $userId]);

        try {
            $this->changePasswordService->execute($passwordChangePayload);
        } catch (DomainException $e) {
            return Response::json(['message' => $e->getMessage()], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return Response::json(['message' => 'Password changed successfully.']);
    }

    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return Response::json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $dto = UserPreferencesData::fromArray($request->validated());
        $user = $this->updatePreferencesService->execute(['user_id' => $userId] + $dto->toArray());

        return Response::json(new UserResource($user));
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return Response::json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $file = $request->file('avatar');

        $user = $this->uploadAvatarService->execute([
            'user_id' => $userId,
            'file'    => [
                'tmp_path'  => $file->getRealPath(),
                'name'      => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size'      => $file->getSize(),
            ],
        ]);

        return Response::json(new UserResource($user));
    }

    public function listDevices(ListUserDeviceRequest $request): UserDeviceCollection|JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return Response::json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

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

    public function upsertDevice(UpsertUserDeviceRequest $request): UserDeviceResource|JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return Response::json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $device = $this->upsertUserDeviceService->execute([
            'user_id' => $userId,
            ...$request->validated(),
        ]);

        return new UserDeviceResource($device);
    }

    public function deleteDevice(int $deviceId): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return Response::json(['message' => 'Unauthenticated'], HttpResponse::HTTP_UNAUTHORIZED);
        }

        $this->deleteUserDeviceService->execute([
            'user_id' => $userId,
            'device_id' => $deviceId,
        ]);

        return Response::json(['message' => 'Device deleted successfully']);
    }

    private function authenticatedUserId(): ?int
    {
        $authenticatable = $this->getAuthenticatedUser->execute();
        if (! $authenticatable) {
            return null;
        }

        return (int) $authenticatable->getAuthIdentifier();
    }
}
