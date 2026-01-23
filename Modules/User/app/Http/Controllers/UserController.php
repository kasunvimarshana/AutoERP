<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Requests\StoreUserRequest;
use Modules\User\Requests\UpdateUserRequest;
use Modules\User\Resources\UserResource;
use Modules\User\Services\UserService;

/**
 * User Controller
 * 
 * Handles HTTP requests for User operations
 * Follows Controller → Service → Repository pattern
 */
class UserController extends Controller
{
    /**
     * UserController constructor
     *
     * @param UserService $userService
     */
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    /**
     * Display a listing of users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $users = $this->userService->getAll($filters);

        return $this->successResponse(
            UserResource::collection($users),
            __('user::messages.users_retrieved')
        );
    }

    /**
     * Store a newly created user
     *
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return $this->createdResponse(
            new UserResource($user),
            __('user::messages.user_created')
        );
    }

    /**
     * Display the specified user
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getById($id);

        return $this->successResponse(
            new UserResource($user),
            __('user::messages.user_retrieved')
        );
    }

    /**
     * Update the specified user
     *
     * @param UpdateUserRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->update($id, $request->validated());

        return $this->successResponse(
            new UserResource($user),
            __('user::messages.user_updated')
        );
    }

    /**
     * Remove the specified user
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->userService->delete($id);

        return $this->successResponse(
            null,
            __('user::messages.user_deleted')
        );
    }

    /**
     * Assign role to user
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = $this->userService->assignRole($id, $request->input('role'));

        return $this->successResponse(
            new UserResource($user->load('roles')),
            __('user::messages.role_assigned')
        );
    }

    /**
     * Revoke role from user
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function revokeRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = $this->userService->revokeRole($id, $request->input('role'));

        return $this->successResponse(
            new UserResource($user->load('roles')),
            __('user::messages.role_revoked')
        );
    }
}
