<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Application\Contracts\AuthServiceInterface;
use Modules\Auth\Application\DTOs\LoginData;
use Modules\Auth\Application\DTOs\RegisterUserData;
use Modules\Auth\Domain\Exceptions\InvalidCredentialsException;
use Modules\Auth\Domain\Exceptions\UserNotFoundException;
use Modules\Auth\Infrastructure\Http\Requests\LoginRequest;
use Modules\Auth\Infrastructure\Http\Requests\RegisterRequest;
use Modules\Auth\Infrastructure\Http\Resources\UserResource;

/**
 * @OA\Tag(name="Auth", description="Authentication endpoints")
 */
final class AuthController extends Controller
{
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Auth"},
     *     summary="Register a new user",
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RegisterRequest")),
     *     @OA\Response(response=201, description="User registered successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterUserData::fromArray($request->validated());
        $dto->tenant_id = (int) $request->header('X-Tenant-ID', 0) ?: null;

        $result = $this->authService->register($dto);

        return response()->json([
            'data'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     summary="Authenticate a user",
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/LoginRequest")),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginData::fromArray($request->validated());

        try {
            $result = $this->authService->login($dto);
        } catch (InvalidCredentialsException | UserNotFoundException $e) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json([
            'data'  => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Auth"},
     *     summary="Revoke the current user's tokens",
     *     security={{"passport":{}}},
     *     @OA\Response(response=204, description="Logged out")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout((int) $request->user()->getAuthIdentifier());

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Auth"},
     *     summary="Get the authenticated user",
     *     security={{"passport":{}}},
     *     @OA\Response(response=200, description="Authenticated user details")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me((int) $request->user()->getAuthIdentifier());

        return (new UserResource($user))->response();
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"Auth"},
     *     summary="Refresh an access token (use Passport OAuth endpoint instead)",
     *     @OA\Response(response=501, description="Use /oauth/token with grant_type=refresh_token")
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Use POST /oauth/token with grant_type=refresh_token to refresh your token.',
        ], 501);
    }
}
