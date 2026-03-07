<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->userService->paginate(
            perPage: (int) $request->get('per_page', 15),
            relations: ['roles', 'tenant'],
            filters: $request->only(['status', 'tenant_id']),
            sortBy: $request->get('sort_by', 'id'),
            sortDirection: $request->get('sort_direction', 'asc'),
            search: $request->get('search', '')
        );

        return $this->paginatedResponse(
            $paginator->through(fn ($u) => new UserResource($u))
        );
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());
        return $this->createdResponse(new UserResource($user->load('roles')));
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findById($id, ['*'], ['roles', 'permissions', 'tenant']);

        if (!$user) {
            return $this->notFoundResponse('User not found');
        }

        return $this->successResponse(new UserResource($user));
    }

    public function update(UserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->updateUser($id, $request->validated());
        return $this->successResponse(new UserResource($user->load('roles')), 'User updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->userService->delete($id);

        if (!$deleted) {
            return $this->notFoundResponse('User not found');
        }

        return $this->successResponse(null, 'User deleted');
    }

    public function activate(int $id): JsonResponse
    {
        $user = $this->userService->activateUser($id);
        return $this->successResponse(new UserResource($user), 'User activated');
    }

    public function deactivate(int $id): JsonResponse
    {
        $user = $this->userService->deactivateUser($id);
        return $this->successResponse(new UserResource($user), 'User deactivated');
    }
}
