<?php

namespace Modules\User\Infrastructure\Http\Controllers;

use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Infrastructure\Http\Requests\StoreUserRequest;
use Modules\User\Infrastructure\Http\Requests\UpdateUserRequest;
use Modules\User\Infrastructure\Http\Requests\UpdatePreferencesRequest;
use Modules\User\Infrastructure\Http\Resources\UserResource;
use Modules\User\Infrastructure\Http\Resources\UserCollection;
use Modules\User\Domain\Entities\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    public function __construct(
        CreateUserServiceInterface $createService,
        protected UpdateUserServiceInterface $updateService,
        protected DeleteUserServiceInterface $deleteService,
        protected AssignRoleServiceInterface $assignRoleService,
        protected UpdatePreferencesServiceInterface $updatePreferencesService
    ) {
        parent::__construct($createService, UserResource::class, UserData::class);
    }

    public function index(Request $request): UserCollection
    {
        $this->authorize('viewAny', User::class);
        $filters = $request->only(['name', 'email', 'active', 'role']);
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $sort = $request->input('sort');
        $include = $request->input('include');

        $users = $this->service->list($filters, $perPage, $page, $sort, $include);
        return new UserCollection($users);
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $this->authorize('create', User::class);
        $dto = UserData::fromArray($request->validated());
        $user = $this->service->execute($dto->toArray());
        return new UserResource($user);
    }

    public function show(int $id): UserResource
    {
        $user = $this->service->find($id);
        if (!$user) {
            abort(404);
        }
        $this->authorize('view', $user);
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, int $id): UserResource
    {
        $user = $this->service->find($id);
        if (!$user) {
            abort(404);
        }
        $this->authorize('update', $user);
        $validated = $request->validated();
        $validated['id'] = $id;
        $dto = UserData::fromArray($validated);
        $updated = $this->updateService->execute($dto->toArray());
        return new UserResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->service->find($id);
        if (!$user) {
            abort(404);
        }
        $this->authorize('delete', $user);
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function assignRole(Request $request, int $id): JsonResponse
    {
        $user = $this->service->find($id);
        if (!$user) {
            abort(404);
        }
        $this->authorize('assignRole', $user);
        $validated = $request->validate(['role_id' => 'required|integer|exists:roles,id']);
        $this->assignRoleService->execute(['user_id' => $id, 'role_id' => $validated['role_id']]);
        return response()->json(['message' => 'Role assigned successfully']);
    }

    public function updatePreferences(UpdatePreferencesRequest $request, int $id): UserResource
    {
        $user = $this->service->find($id);
        if (!$user) {
            abort(404);
        }
        $this->authorize('updatePreferences', $user);
        $validated = $request->validated();
        $dto = UserPreferencesData::fromArray($validated);
        $updated = $this->updatePreferencesService->execute(['user_id' => $id] + $dto->toArray());
        return new UserResource($updated);
    }

    protected function getModelClass(): string
    {
        return User::class;
    }
}
