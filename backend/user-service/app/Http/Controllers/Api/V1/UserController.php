<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\User::class);

        $users = $this->userService->list(
            $request->only(['search', 'role', 'is_active', 'department', 'region', 'sort_by', 'sort_dir']),
            $request->filled('per_page') ? (int) $request->input('per_page') : null,
        );

        return response()->json(UserResource::collection($users)->response()->getData(true));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Models\User::class);

        $data = array_merge($request->validated(), ['tenant_id' => app('current_tenant_id')]);
        $user = $this->userService->create($data);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => new UserResource($user),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        $this->authorize('view', $user);

        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        $this->authorize('update', $user);

        $updated = $this->userService->update($id, $request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new UserResource($updated),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        $this->authorize('delete', $user);

        $this->userService->delete($id);

        return response()->json(['message' => 'User deleted successfully.'], 204);
    }

    public function activate(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        $this->authorize('update', $user);

        $updated = $this->userService->activate($id);

        return response()->json(['message' => 'User activated.', 'data' => new UserResource($updated)]);
    }

    public function deactivate(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        $this->authorize('update', $user);

        $updated = $this->userService->deactivate($id);

        return response()->json(['message' => 'User deactivated.', 'data' => new UserResource($updated)]);
    }

    public function updateRoles(Request $request, int $id): JsonResponse
    {
        $request->validate(['roles' => 'required|array', 'roles.*' => 'string']);

        $user = $this->userService->findById($id);
        $this->authorize('manage-roles', $user);

        $updated = $this->userService->updateRoles($id, $request->input('roles'));

        return response()->json(['message' => 'Roles updated.', 'data' => new UserResource($updated)]);
    }

    public function updateAttributes(Request $request, int $id): JsonResponse
    {
        $request->validate(['attributes' => 'required|array']);

        $user = $this->userService->findById($id);
        $this->authorize('manage-attributes', $user);

        $updated = $this->userService->updateAttributes($id, $request->input('attributes'));

        return response()->json(['message' => 'Attributes updated.', 'data' => new UserResource($updated)]);
    }
}
