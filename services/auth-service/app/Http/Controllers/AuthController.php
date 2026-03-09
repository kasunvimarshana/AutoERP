<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\AuthServiceInterface;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\TokenResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Auth Controller
 *
 * Handles HTTP requests for authentication operations.
 * Thin controller - delegates all business logic to AuthService.
 */
class AuthController extends Controller
{
    public function __construct(
        protected readonly AuthServiceInterface $authService
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => new TokenResource($result),
            ],
        ], 201);
    }

    /**
     * Login a user and return access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => new TokenResource($result),
            ],
        ]);
    }

    /**
     * Logout user by revoking all tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Refresh the access token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refreshToken(
            $request->bearerToken() ?? ''
        );

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed.',
            'data' => new TokenResource($result),
        ]);
    }

    /**
     * Validate a token and return user info (for inter-service auth).
     */
    public function validate(Request $request): JsonResponse
    {
        $userData = $this->authService->validateToken(
            $request->bearerToken() ?? ''
        );

        if (!$userData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $userData,
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()->load('roles.permissions')),
        ]);
    }
}
