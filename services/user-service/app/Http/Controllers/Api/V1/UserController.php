<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * GET /api/v1/users
     *
     * Supports: ?search=, ?filter[role]=, ?filter[status]=, ?sort=name, ?per_page=15
     */
    public function index(Request $request): UserCollection
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        $sortParam = (string) $request->query('sort', 'created_at');
        $paginator = $this->userService->listUsers(
            tenantId: $tenantId,
            perPage:  (int) $request->query('per_page', 15),
            filters:  $request->query('filter', []),
            sortBy:   ltrim($sortParam, '-'),
            sortDir:  str_starts_with($sortParam, '-') ? 'desc' : 'asc',
            search:   $request->query('search'),
        );

        return new UserCollection($paginator);
    }

    /**
     * GET /api/v1/users/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        $user = $this->userService->getUser($id, $tenantId);

        if (! $user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(new UserResource($user));
    }

    /**
     * POST /api/v1/users
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        $dto = UserDTO::fromArray(array_merge(
            $request->validated(),
            ['tenant_id' => $tenantId],
        ));

        try {
            $user = $this->userService->createUser($dto);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return response()->json(new UserResource($user), Response::HTTP_CREATED);
    }

    /**
     * PUT /api/v1/users/{id}
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        // Merge existing user data with the partial update payload
        $existing = $this->userService->getUser($id, $tenantId);

        if (! $existing) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = array_merge($existing->toArray(), $request->validated());
        $dto  = UserDTO::fromArray(array_merge($data, ['tenant_id' => $tenantId]));

        try {
            $user = $this->userService->updateUser($id, $tenantId, $dto);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return response()->json(new UserResource($user));
    }

    /**
     * DELETE /api/v1/users/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        try {
            $this->userService->deleteUser($id, $tenantId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * POST /api/v1/users/{id}/restore
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        $this->userService->restoreUser($id, $tenantId);

        $user = $this->userService->getUser($id, $tenantId);

        return response()->json(new UserResource($user));
    }
}
