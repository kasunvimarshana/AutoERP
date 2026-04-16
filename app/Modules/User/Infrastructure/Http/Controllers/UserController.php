<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Application\DTOs\UserPreferencesData;
use Modules\User\Domain\Entities\User;
use Modules\User\Infrastructure\Http\Requests\AssignRoleRequest;
use Modules\User\Infrastructure\Http\Requests\StoreUserRequest;
use Modules\User\Infrastructure\Http\Requests\UpdatePreferencesRequest;
use Modules\User\Infrastructure\Http\Requests\UpdateUserRequest;
use Modules\User\Infrastructure\Http\Resources\UserCollection;
use Modules\User\Infrastructure\Http\Resources\UserResource;
use OpenApi\Attributes as OA;

class UserController extends AuthorizedController
{
    public function __construct(
        protected FindUserServiceInterface $findService,
        protected CreateUserServiceInterface $createService,
        protected UpdateUserServiceInterface $updateService,
        protected DeleteUserServiceInterface $deleteService,
        protected AssignRoleServiceInterface $assignRoleService,
        protected UpdatePreferencesServiceInterface $updatePreferencesService
    ) {}

    public function index(Request $request): UserCollection
    {
        $this->authorize('viewAny', User::class);
        $filters = $request->only(['name', 'email', 'active', 'role']);

        if ($request->has('active')) {
            $filters['active'] = $request->boolean('active');
        }

        $perPage = $request->integer('per_page', 15);
        $page = $request->integer('page', 1);
        $sort = $request->input('sort');
        $include = $request->input('include');

        $users = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new UserCollection($users);
    }


    public function store(StoreUserRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', User::class);
        $dto = UserData::fromArray($request->validated());
        $user = $this->createService->execute($dto->toArray());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    
    public function show(int $id): UserResource
    {
        $user = $this->findService->find($id);
        if (! $user) {
            abort(404);
        }
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    
    public function update(UpdateUserRequest $request, int $id): UserResource
    {
        $user = $this->findService->find($id);
        if (! $user) {
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
        $user = $this->findService->find($id);
        if (! $user) {
            abort(404);
        }
        $this->authorize('delete', $user);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'User deleted successfully']);
    }


    public function assignRole(AssignRoleRequest $request, int $id): JsonResponse
    {
        $user = $this->findService->find($id);
        if (! $user) {
            abort(404);
        }
        $this->authorize('assignRole', $user);
        $validated = $request->validated();
        $this->assignRoleService->execute(['user_id' => $id, 'role_id' => $validated['role_id']]);

        return response()->json(['message' => 'Role assigned successfully']);
    }


    public function updatePreferences(UpdatePreferencesRequest $request, int $id): UserResource
    {
        $user = $this->findService->find($id);
        if (! $user) {
            abort(404);
        }
        $this->authorize('updatePreferences', $user);
        $validated = $request->validated();
        $dto = UserPreferencesData::fromArray($validated);
        $updated = $this->updatePreferencesService->execute(['user_id' => $id] + $dto->toArray());

        return new UserResource($updated);
    }


}
