<?php
namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = app('tenant');
        $filters = $request->only(['search', 'role', 'is_active']);
        $users = $this->userService->listUsers($filters, $tenant->id);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->userService->getUser($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
            'is_active' => 'boolean',
            'attributes' => 'nullable|array',
        ]);

        $tenant = app('tenant');
        $user = $this->userService->createUser($validated, $tenant->id);

        return response()->json(['success' => true, 'data' => $user, 'message' => 'User created successfully'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$id}",
            'password' => ['sometimes', Password::min(8)],
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
            'is_active' => 'sometimes|boolean',
            'attributes' => 'nullable|array',
        ]);

        $user = $this->userService->updateUser($id, $validated);

        return response()->json(['success' => true, 'data' => $user, 'message' => 'User updated successfully']);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->userService->deleteUser($id);
        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    }
}
