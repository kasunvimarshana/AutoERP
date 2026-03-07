<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Requests\CreateUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage  = (int) $request->query('per_page', 15);
        $search   = $request->query('search');
        $sortBy   = $request->query('sort_by', 'created_at');
        $sortDir  = $request->query('sort_dir', 'desc');
        $filters  = $request->query('filter', []);

        $users = $this->userService->listUsers($perPage, $filters, $search, $sortBy, $sortDir);

        return UserResource::collection($users);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getUser($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json(new UserResource($user));
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());
            return response()->json(new UserResource($user), 201);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->userService->updateUser($id, $request->validated());
            return response()->json(new UserResource($user));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->userService->deleteUser($id);

            if (!$deleted) {
                return response()->json(['error' => 'User not found'], 404);
            }

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
