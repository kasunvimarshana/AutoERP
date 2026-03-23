<?php

namespace Modules\User\Infrastructure\Http\Controllers;

use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\User\Application\Services\CreateUserService;
use Modules\User\Application\Services\UpdateUserService;
use Modules\User\Application\Services\DeleteUserService;
use Modules\User\Application\Services\AssignRoleService;
use Modules\User\Application\Services\UpdatePreferencesService;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Infrastructure\Http\Resources\UserResource;
use Modules\User\Infrastructure\Http\Resources\UserCollection;
use Modules\User\Domain\Entities\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    public function __construct(
        CreateUserService $createService,
        UpdateUserService $updateService,
        DeleteUserService $deleteService,
        protected AssignRoleService $assignRoleService,
        protected UpdatePreferencesService $updatePreferencesService
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

    public function store(Request $request): UserResource
    {
        $this->authorize('create', User::class);
        $validated = $request->validate((new UserData())->rules());
        $dto = UserData::fromArray($validated);
        $user = $this->service->execute($dto->toArray());
        return new UserResource($user);
    }

    public function show($id): UserResource
    {
        $user = $this->service->find($id);
        if (!$user) {
            abort(404);
        }
        $this->authorize('view', $user);
        return new UserResource($user);
    }

    public function update(Request $request, $id): UserResource
    {
        $user = $this->service->find($id);
        if (!$user) {
            abort(404);
        }
        $this->authorize('update', $user);
        $validated = $request->validate((new UserData())->rules());
        $validated['id'] = $id;
        $dto = UserData::fromArray($validated);
        $updated = $this->updateService->execute($dto->toArray());
        return new UserResource($updated);
    }

    public function destroy($id): JsonResponse
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
}
