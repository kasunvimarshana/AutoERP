<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Contracts\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\UserResource;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PermissionService $permissionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $users = $this->userRepository->findAll($request->all());

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($users),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = $this->userRepository->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user->load(['roles', 'permissions', 'tenant'])),
        ]);
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = $this->userRepository->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data'    => new UserResource($user),
        ]);
    }

    public function updateRoles(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'roles'     => ['required', 'array'],
            'roles.*'   => ['string'],
            'tenant_id' => ['sometimes', 'nullable', 'string'],
        ]);

        $user = $this->permissionService->syncRoles(
            userId: $id,
            roles: $request->roles,
            teamId: $request->input('tenant_id'),
        );

        return response()->json([
            'success' => true,
            'message' => 'User roles updated successfully.',
            'data'    => new UserResource($user->load('roles')),
        ]);
    }

    public function updatePermissions(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string'],
            'tenant_id'     => ['sometimes', 'nullable', 'string'],
        ]);

        $user = $this->permissionService->syncPermissions(
            userId: $id,
            permissions: $request->permissions,
            teamId: $request->input('tenant_id'),
        );

        return response()->json([
            'success' => true,
            'message' => 'User permissions updated successfully.',
            'data'    => new UserResource($user->load('permissions')),
        ]);
    }
}
