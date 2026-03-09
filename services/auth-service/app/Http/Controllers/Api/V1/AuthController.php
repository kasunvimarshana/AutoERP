<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Contracts\AuthServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            email: $request->email,
            password: $request->password,
            tenantId: $request->input('tenant_id'),
            deviceId: $request->input('device_id'),
            deviceName: $request->input('device_name'),
            rememberMe: (bool) $request->input('remember_me', false),
        );

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => new AuthResource(
                $result['user'],
                $result['access_token'],
                $result['refresh_token'] ?? null,
                'Bearer',
                $result['expires_in'] ?? 0,
            ),
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data'    => new AuthResource(
                $result['user'],
                $result['access_token'],
                $result['refresh_token'] ?? null,
                'Bearer',
                $result['expires_in'] ?? 0,
            ),
        ], Response::HTTP_CREATED);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout(
            user: Auth::user(),
            deviceId: $request->header('X-Device-ID'),
            revokeAll: $request->boolean('revoke_all', false),
        );

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $result = $this->authService->refreshToken($request->refresh_token);

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed.',
            'data'    => [
                'access_token'  => $result['access_token'],
                'refresh_token' => $result['refresh_token'] ?? null,
                'token_type'    => 'Bearer',
                'expires_in'    => $result['expires_in'] ?? 0,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = Auth::user()->load(['roles', 'permissions', 'tenant']);

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink(
            email: $request->email,
            tenantId: $request->input('tenant_id'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email address.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            token: $request->token,
            email: $request->email,
            password: $request->password,
            tenantId: $request->input('tenant_id'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
        ]);
    }
}
