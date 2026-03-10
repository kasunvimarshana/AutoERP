<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * UserController
 *
 * Thin controller — delegates all logic to UserService.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    // GET /api/users
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $filters  = $request->only(['is_active', 'name:like', 'email:like']);
        $perPage  = (int) $request->get('per_page', 15);

        $users = $this->userService->list($tenantId, $filters, $perPage);

        return response()->json($users);
    }

    // GET /api/users/{id}
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $tenantId = $request->attributes->get('tenant_id');
            $user     = $this->userService->findById($id, $tenantId);

            return response()->json(['data' => $user]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true], 404);
        }
    }

    // PUT /api/users/{id}
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name'       => ['sometimes', 'string', 'max:255'],
            'phone'      => ['sometimes', 'nullable', 'string', 'max:30'],
            'avatar_url' => ['sometimes', 'nullable', 'url'],
            'address'    => ['sometimes', 'array'],
            'timezone'   => ['sometimes', 'string', 'max:64'],
            'locale'     => ['sometimes', 'string', 'max:10'],
        ]);

        try {
            $tenantId = $request->attributes->get('tenant_id');
            $user     = $this->userService->upsertProfile(
                array_merge($request->validated(), [
                    'tenant_id'    => $tenantId,
                    'auth_user_id' => $id,
                ])
            );

            return response()->json(['data' => $user]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true], $e->getCode() ?: 422);
        }
    }

    // PATCH /api/users/{id}/preferences
    public function updatePreferences(Request $request, string $id): JsonResponse
    {
        $request->validate(['preferences' => ['required', 'array']]);

        try {
            $tenantId = $request->attributes->get('tenant_id');
            $user     = $this->userService->updatePreferences($id, $tenantId, $request->input('preferences'));

            return response()->json(['data' => $user]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true], 404);
        }
    }

    // POST /api/users/{id}/roles
    public function assignRole(Request $request, string $id): JsonResponse
    {
        $request->validate(['role' => ['required', 'string']]);

        try {
            $tenantId = $request->attributes->get('tenant_id');
            $this->userService->assignRole($id, $tenantId, $request->input('role'));

            return response()->json(['message' => 'Role assigned successfully.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true], 422);
        }
    }

    // DELETE /api/users/{id}
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $tenantId = $request->attributes->get('tenant_id');
            $this->userService->delete($id, $tenantId);

            return response()->json(['message' => 'User deleted successfully.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true], 404);
        }
    }
}
