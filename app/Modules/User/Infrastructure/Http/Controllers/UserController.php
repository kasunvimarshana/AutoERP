<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\FindUserAttachmentsServiceInterface;
use Modules\User\Application\Contracts\FindUserDevicesServiceInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Domain\Entities\User;
use Modules\User\Infrastructure\Http\Requests\AssignRoleRequest;
use Modules\User\Infrastructure\Http\Requests\ListUserRequest;
use Modules\User\Infrastructure\Http\Requests\StoreUserRequest;
use Modules\User\Infrastructure\Http\Requests\UpdatePreferencesRequest;
use Modules\User\Infrastructure\Http\Requests\UpdateUserRequest;
use Modules\User\Infrastructure\Http\Resources\UserCollection;
use Modules\User\Infrastructure\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends AuthorizedController
{
    /** @var array<string> */
    private const SUPPORTED_INCLUDES = ['attachments', 'devices', 'permissions'];

    public function __construct(
        protected FindUserServiceInterface $findUserService,
        protected FindUserAttachmentsServiceInterface $findUserAttachmentsService,
        protected FindUserDevicesServiceInterface $findUserDevicesService,
        protected CreateUserServiceInterface $createUserService,
        protected UpdateUserServiceInterface $updateUserService,
        protected DeleteUserServiceInterface $deleteUserService,
        protected AssignRoleServiceInterface $assignRoleService,
        protected UpdatePreferencesServiceInterface $updatePreferencesService,
        protected UploadAvatarServiceInterface $uploadAvatarService
    ) {}

    public function index(ListUserRequest $request): UserCollection
    {
        $this->authorize('viewAny', User::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'email' => $validated['email'] ?? null,
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        if (array_key_exists('active', $validated)) {
            $filters['status'] = (bool) $validated['active'] ? 'active' : 'inactive';
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sort = $validated['sort'] ?? null;
        $include = $validated['include'] ?? null;

        $users = $this->findUserService->list($filters, $perPage, $page, $sort, $include);

        $normalizedIncludes = $this->parseIncludes($include);
        if ($normalizedIncludes !== []) {
            $users->setCollection(
                $users->getCollection()->map(
                    fn (User $user): UserResource => $this->buildIndexedUserResource($user, $normalizedIncludes)
                )
            );
        }

        return new UserCollection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $validated = $request->validated();
        $avatarFile = $request->file('avatar_file');

        $createdUser = DB::transaction(function () use ($validated, $avatarFile): User {
            $payload = $validated;
            unset($payload['avatar_file']);

            $dto = UserData::fromArray($payload);
            $savedUser = $this->createUserService->execute($dto->toArray());

            if ($avatarFile !== null && $savedUser->getId() !== null) {
                $this->uploadAvatarService->execute([
                    'user_id' => $savedUser->getId(),
                    'file' => [
                        'tmp_path' => $avatarFile->getPathname(),
                        'name' => $avatarFile->getClientOriginalName(),
                        'mime_type' => (string) $avatarFile->getMimeType(),
                        'size' => (int) $avatarFile->getSize(),
                    ],
                ]);

                return $this->findUserService->find($savedUser->getId()) ?? $savedUser;
            }

            return $savedUser;
        });

        $resource = $this->buildUserResource($createdUser, $request->query('include'));

        return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $userId): UserResource
    {
        $userEntity = $this->findUserOrFail($userId);
        $this->authorize('view', $userEntity);

        return $this->buildUserResource($userEntity, $request->query('include'));
    }

    public function update(UpdateUserRequest $request, int $userId): UserResource
    {
        $userEntity = $this->findUserOrFail($userId);
        $this->authorize('update', $userEntity);

        $validated = $request->validated();
        $avatarFile = $request->file('avatar_file');

        $updatedUser = DB::transaction(function () use ($validated, $avatarFile, $userId): User {
            $payload = $validated;
            unset($payload['avatar_file']);
            $payload['id'] = $userId;

            $savedUser = $this->updateUserService->execute($payload);

            if ($avatarFile !== null) {
                $this->uploadAvatarService->execute([
                    'user_id' => $userId,
                    'file' => [
                        'tmp_path' => $avatarFile->getPathname(),
                        'name' => $avatarFile->getClientOriginalName(),
                        'mime_type' => (string) $avatarFile->getMimeType(),
                        'size' => (int) $avatarFile->getSize(),
                    ],
                ]);

                return $this->findUserService->find($userId) ?? $savedUser;
            }

            return $savedUser;
        });

        return $this->buildUserResource($updatedUser, $request->query('include'));
    }

    public function destroy(int $userId): JsonResponse
    {
        $userEntity = $this->findUserOrFail($userId);
        $this->authorize('delete', $userEntity);
        $this->deleteUserService->execute(['id' => $userId]);

        return Response::json(['message' => 'User deleted successfully']);
    }

    public function assignRole(AssignRoleRequest $request, int $userId): JsonResponse
    {
        $userEntity = $this->findUserOrFail($userId);
        $this->authorize('assignRole', $userEntity);
        $validated = $request->validated();
        $this->assignRoleService->execute(['user_id' => $userId, 'role_id' => $validated['role_id']]);

        return Response::json(['message' => 'Role assigned successfully']);
    }

    public function updatePreferences(UpdatePreferencesRequest $request, int $userId): UserResource
    {
        $userEntity = $this->findUserOrFail($userId);
        $this->authorize('updatePreferences', $userEntity);
        $validated = $request->validated();
        $dto = UserPreferencesData::fromArray($validated);
        $updatedUser = $this->updatePreferencesService->execute(['user_id' => $userId] + $dto->toArray());

        return $this->buildUserResource($updatedUser, $request->query('include'));
    }

    private function findUserOrFail(int $userId): User
    {
        $user = $this->findUserService->find($userId);
        if (! $user) {
            throw new NotFoundHttpException('User not found.');
        }

        return $user;
    }

    private function buildUserResource(User $user, mixed $includeValue): UserResource
    {
        $includes = $this->parseIncludes($includeValue);
        $userId = $user->getId();

        return new UserResource(
            resource: $user,
            attachments: in_array('attachments', $includes, true) && $userId !== null
                ? $this->findUserAttachmentsService->getByUser($userId)
                : null,
            devices: in_array('devices', $includes, true) && $userId !== null
                ? $this->collectUserDevices($userId)
                : null,
            includePermissions: in_array('permissions', $includes, true)
        );
    }

    /**
     * @return array<int, string>
     */
    private function parseIncludes(mixed $includeValue): array
    {
        if (! is_string($includeValue) || trim($includeValue) === '') {
            return [];
        }

        $requestedIncludes = array_map('trim', explode(',', $includeValue));

        return array_values(array_unique(array_filter(
            $requestedIncludes,
            fn (string $include): bool => in_array($include, self::SUPPORTED_INCLUDES, true)
        )));
    }

    private function collectUserDevices(int $userId): Collection
    {
        return Collection::make(
            $this->findUserDevicesService
                ->paginateByUser($userId, null, 100, 1)
                ->items()
        );
    }

    /**
     * @param array<int, string> $includes
     */
    private function buildIndexedUserResource(User $user, array $includes): UserResource
    {
        return new UserResource(
            resource: $user,
            includePermissions: in_array('permissions', $includes, true)
        );
    }
}
