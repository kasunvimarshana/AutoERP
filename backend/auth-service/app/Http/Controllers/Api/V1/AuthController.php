<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Registration successful.',
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $result = $this->authService->login($request->validated(), $tenantId);

        return response()->json([
            'message' => 'Login successful.',
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        // Passport handles refresh via /oauth/token with grant_type=refresh_token
        return response()->json(['message' => 'Use /oauth/token with grant_type=refresh_token'], 400);
    }

    public function introspect(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        // Validate shared secret for service-to-service calls
        $secret = $request->header('X-Service-Secret');
        if ($secret !== config('services.auth_service.shared_secret')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $result = $this->authService->introspect($request->input('token'));

        return response()->json($result);
    }
}
