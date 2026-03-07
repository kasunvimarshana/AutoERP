<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AuthServiceInterface;
use App\Contracts\TenantServiceInterface;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles authentication endpoints: register, login, logout, refresh.
 *
 * All responses are JSON-formatted and tenant-aware.
 */
final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface   $authService,
        private readonly TenantServiceInterface $tenantService,
    ) {}

    /**
     * POST /api/v1/auth/register
     *
     * Register a new user within a tenant.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->resolveFromRequest($request);

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        $result = $this->authService->register(
            array_merge($request->validated(), ['tenant_id' => $tenant->id])
        );

        return response()->json([
            'message' => 'User registered successfully.',
            'data'    => [
                'user'  => $result['user'],
                'token' => $result['token'],
            ],
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     *
     * Authenticate a user and return a Passport Bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $tokenData = $this->authService->login($request->validated());

        if (!$tokenData) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json([
            'message' => 'Login successful.',
            'data'    => $tokenData,
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Revoke the current user's access tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * POST /api/v1/auth/refresh
     *
     * Issue a new access token using a refresh token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');

        if (!$refreshToken) {
            return response()->json(['message' => 'Refresh token is required.'], 422);
        }

        $tokenData = $this->authService->refreshToken($refreshToken);

        if (!$tokenData) {
            return response()->json(['message' => 'Invalid or expired refresh token.'], 401);
        }

        return response()->json([
            'message' => 'Token refreshed successfully.',
            'data'    => $tokenData,
        ]);
    }

    /**
     * GET /api/v1/auth/me
     *
     * Return the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('tenant', 'roles', 'permissions');

        return response()->json([
            'message' => 'User profile retrieved.',
            'data'    => $user,
        ]);
    }
}
