<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Services\Auth\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SSO Authentication controller.
 *
 * Handles registration, login (Passport token issuance), logout, and
 * returning the authenticated user profile.
 */
final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * POST /api/v1/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $tenantId = app('tenant.manager')->getCurrentTenantId();

        $attributes = array_merge($request->validated(), ['tenant_id' => $tenantId]);

        $user = $this->authService->register($attributes);

        return response()->json([
            'message' => 'Registration successful.',
            'user'    => $user->only(['id', 'name', 'email', 'tenant_id']),
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->string('email')->lower()->toString(),
            $request->string('password')->toString()
        );

        return response()->json([
            'message'      => 'Login successful.',
            'access_token' => $result['token'],
            'token_type'   => $result['token_type'],
            'user'         => $result['user']->only(['id', 'name', 'email', 'tenant_id']),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout(Auth::id());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me(Auth::id());

        return response()->json(['user' => $user]);
    }

    /**
     * POST /api/v1/auth/roles
     */
    public function assignRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['required', 'string'],
        ]);

        $user = $this->authService->assignRole(
            $request->integer('user_id'),
            $request->string('role')->toString()
        );

        return response()->json([
            'message' => "Role '{$request->input('role')}' assigned.",
            'user_id' => $user->id,
        ]);
    }
}
