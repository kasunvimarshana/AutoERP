<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Auth\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\Auth\Infrastructure\Http\Requests\StoreUserRequest;
use Modules\Auth\Infrastructure\Http\Requests\UpdateUserRequest;
use Modules\Auth\Infrastructure\Http\Resources\UserResource;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;

/**
 * @OA\Tag(name="Users", description="User management endpoints")
 */
final class UserController extends AuthorizedController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="List users for the current tenant",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated user list")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', UserResource::class);

        $tenantId = (int) $request->user()->tenant_id;
        $perPage  = (int) $request->query('per_page', 15);

        $paginated = $this->userRepository
            ->where('tenant_id', $tenantId)
            ->paginate($perPage);

        return UserResource::collection($paginated);
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     security={{"passport":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreUserRequest")),
     *     @OA\Response(response=201, description="User created")
     * )
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', UserResource::class);

        $data             = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepository->create($data);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Get a user by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $user): JsonResponse
    {
        $record = $this->userRepository->find($user);

        $this->authorize('view', $record);

        return (new UserResource($record))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Update a user",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateUserRequest")),
     *     @OA\Response(response=200, description="User updated")
     * )
     */
    public function update(UpdateUserRequest $request, int $user): JsonResponse
    {
        $record = $this->userRepository->find($user);

        $this->authorize('update', $record);

        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $updated = $this->userRepository->update($user, $data);

        return (new UserResource($updated))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Soft-delete a user",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(int $user): JsonResponse
    {
        $record = $this->userRepository->find($user);

        $this->authorize('delete', $record);

        $this->userRepository->delete($user);

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/users/{id}/roles",
     *     tags={"Users"},
     *     summary="Assign a role to a user",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"role_id"},
     *         @OA\Property(property="role_id", type="integer"),
     *         @OA\Property(property="tenant_id", type="integer", nullable=true)
     *     )),
     *     @OA\Response(response=204, description="Role assigned")
     * )
     */
    public function assignRole(Request $request, int $user): JsonResponse
    {
        $this->authorize('assignRole', $this->userRepository->find($user));

        $request->validate([
            'role_id'   => ['required', 'integer', 'exists:roles,id'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        $this->authorizationService->assignRole(
            $user,
            (int) $request->input('role_id'),
            $request->input('tenant_id') ? (int) $request->input('tenant_id') : null,
        );

        return response()->json(null, 204);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}/roles/{roleId}",
     *     tags={"Users"},
     *     summary="Revoke a role from a user",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="roleId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Role revoked")
     * )
     */
    public function revokeRole(Request $request, int $user, int $roleId): JsonResponse
    {
        $this->authorize('assignRole', $this->userRepository->find($user));

        $this->authorizationService->revokeRole(
            $user,
            $roleId,
            $request->input('tenant_id') ? (int) $request->input('tenant_id') : null,
        );

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}/permissions",
     *     tags={"Users"},
     *     summary="Get all effective permissions for a user",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Permission slugs")
     * )
     */
    public function permissions(int $user): JsonResponse
    {
        $this->authorize('view', $this->userRepository->find($user));

        $permissions = $this->authorizationService->getUserPermissions($user);

        return response()->json(['data' => $permissions]);
    }
}
