<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Application\UseCases\GetAuthenticatedUser;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Infrastructure\Http\Requests\ChangePasswordRequest;
use Modules\User\Infrastructure\Http\Requests\UpdatePreferencesRequest;
use Modules\User\Infrastructure\Http\Requests\UpdateProfileRequest;
use Modules\User\Infrastructure\Http\Requests\UploadAvatarRequest;
use Modules\User\Infrastructure\Http\Resources\UserResource;
use OpenApi\Attributes as OA;

class ProfileController extends AuthorizedController
{
    public function __construct(
        protected GetAuthenticatedUser $getAuthenticatedUser,
        protected FindUserServiceInterface $findService,
        protected UpdateProfileServiceInterface $updateProfileService,
        protected ChangePasswordServiceInterface $changePasswordService,
        protected UpdatePreferencesServiceInterface $updatePreferencesService,
        protected UploadAvatarServiceInterface $uploadAvatarService
    ) {}

    public function show(): JsonResponse
    {
        $authenticatable = $this->getAuthenticatedUser->execute();

        if (! $authenticatable) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = $this->findService->find($authenticatable->getAuthIdentifier());

        if (! $user) {
            return response()->json(['message' => 'User profile unavailable'], 404);
        }

        return response()->json(new UserResource($user));
    }


    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $authenticatable = $this->getAuthenticatedUser->execute();

        if (! $authenticatable) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $userId = (int) $authenticatable->getAuthIdentifier();
        $data = array_merge($request->validated(), ['user_id' => $userId]);
        $user = $this->updateProfileService->execute($data);

        return response()->json(new UserResource($user));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $authenticatable = $this->getAuthenticatedUser->execute();

        if (! $authenticatable) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $userId = (int) $authenticatable->getAuthIdentifier();
        $data = array_merge($request->validated(), ['user_id' => $userId]);

        try {
            $this->changePasswordService->execute($data);
        } catch (\Modules\Core\Domain\Exceptions\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $authenticatable = $this->getAuthenticatedUser->execute();

        if (! $authenticatable) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $userId = (int) $authenticatable->getAuthIdentifier();
        $dto = UserPreferencesData::fromArray($request->validated());
        $user = $this->updatePreferencesService->execute(['user_id' => $userId] + $dto->toArray());

        return response()->json(new UserResource($user));
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $authenticatable = $this->getAuthenticatedUser->execute();

        if (! $authenticatable) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $userId = (int) $authenticatable->getAuthIdentifier();
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

        return response()->json(new UserResource($user));
    }
}
