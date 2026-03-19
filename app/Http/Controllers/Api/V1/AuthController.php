<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\TokenResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Authenticate user and issue JWT token",
     *     tags={"Auth"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"email","password"},
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="tenant_id", type="string", format="uuid"),
     *         @OA\Property(property="device_id", type="string")
     *     )),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $tokenData = $this->authService->login(
                email: $request->string('email')->value(),
                password: $request->string('password')->value(),
                tenantId: $request->string('tenant_id')->value() ?: null,
                deviceInfo: $request->getDeviceInfo(),
                request: $request
            );

            return response()->json([
                'success' => true,
                'data'    => new TokenResource($tokenData),
                'message' => 'Login successful.',
            ]);
        } catch (AuthException $e) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'AUTH_001',
                    'message' => $e->getMessage(),
                ],
                'trace_id' => (string) Str::uuid(),
            ], $e->getCode() ?: Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Register a new user",
     *     tags={"Auth"}
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            data: $request->validated(),
            tenantId: $request->string('tenant_id')->value() ?: null,
            request: $request
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => new UserResource($result['user']),
                'token' => new TokenResource($result['token']),
            ],
            'message' => 'Registration successful.',
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Revoke current token",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $jti = $user?->token()?->id;

        $this->authService->logout(
            user: $user,
            jti: $jti,
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout-all",
     *     summary="Revoke all tokens across all devices",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAllDevices($request->user(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     summary="Refresh access token",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $oldJti = $request->string('jti')->value();

        $tokenData = $this->authService->refreshToken($user, $oldJti, $request);

        return response()->json([
            'success' => true,
            'data'    => new TokenResource($tokenData),
            'message' => 'Token refreshed.',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="Get authenticated user profile",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new UserResource($request->user()),
        ]);
    }
}
