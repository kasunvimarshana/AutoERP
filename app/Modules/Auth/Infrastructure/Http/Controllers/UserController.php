<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Application\Contracts\UserServiceInterface;
use Modules\Auth\Infrastructure\Http\Resources\UserResource;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;
        $users    = $this->userService->getAllUsers($tenantId);

        return response()->json(UserResource::collection(collect($users)));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;
        $user     = $this->userService->getUser($tenantId, $id);

        return response()->json(new UserResource($user));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => [
                'required',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'password'    => 'required|string|min:8',
            'role'        => 'sometimes|string',
            'status'      => 'sometimes|string',
            'preferences' => 'sometimes|array',
        ]);

        $user = $this->userService->createUser($tenantId, $data);

        return response()->json(new UserResource($user), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'email'       => [
                'sometimes',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')
                    ->where('tenant_id', $tenantId)
                    ->ignore($id)
                    ->whereNull('deleted_at'),
            ],
            'password'    => 'sometimes|string|min:8',
            'role'        => 'sometimes|string',
            'status'      => 'sometimes|string',
            'preferences' => 'sometimes|array',
        ]);

        $user = $this->userService->updateUser($tenantId, $id, $data);

        return response()->json(new UserResource($user));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;

        $this->userService->deleteUser($tenantId, $id);

        return response()->json(null, 204);
    }
}
