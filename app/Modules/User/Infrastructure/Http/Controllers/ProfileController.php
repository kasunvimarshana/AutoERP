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

    #[OA\Get(
        path: '/api/profile',
        summary: 'Get own profile',
        description: 'Returns the full profile of the currently authenticated user.',
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Authenticated user profile',
                content: new OA\JsonContent(ref: '#/components/schemas/UserObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Profile unavailable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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

    #[OA\Patch(
        path: '/api/profile',
        summary: 'Update own profile',
        description: 'Updates the profile details of the currently authenticated user.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['first_name', 'last_name'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', maxLength: 255, example: 'John'),
                    new OA\Property(property: 'last_name',  type: 'string', maxLength: 255, example: 'Doe'),
                    new OA\Property(property: 'phone',      type: 'string', nullable: true, maxLength: 20, example: '+1-555-0100'),
                    new OA\Property(property: 'address',    type: 'object', nullable: true),
                ],
            ),
        ),
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Updated user profile',
                content: new OA\JsonContent(ref: '#/components/schemas/UserObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
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

    #[OA\Post(
        path: '/api/profile/change-password',
        summary: 'Change own password',
        description: 'Changes the password of the currently authenticated user.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password',      type: 'string', format: 'password', example: 'oldSecret1'),
                    new OA\Property(property: 'password',              type: 'string', format: 'password', minLength: 8, example: 'newSecret1'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'newSecret1'),
                ],
            ),
        ),
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Password changed successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation or domain error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
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

    #[OA\Patch(
        path: '/api/profile/preferences',
        summary: 'Update own preferences',
        description: 'Updates the preferences of the currently authenticated user.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserPreferencesObject'),
        ),
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Updated user profile',
                content: new OA\JsonContent(ref: '#/components/schemas/UserObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
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

    #[OA\Post(
        path: '/api/profile/avatar',
        summary: 'Upload own avatar',
        description: 'Uploads a new avatar image for the currently authenticated user.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['avatar'],
                    properties: [
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary'),
                    ],
                ),
            ),
        ),
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Avatar uploaded successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/UserObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
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
