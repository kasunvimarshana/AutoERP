<?php

declare(strict_types=1);

namespace Modules\Auth\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Auth\Application\DTOs\LoginDTO;
use Modules\Auth\Application\DTOs\RegisterDTO;
use Modules\Auth\Application\Services\AuthService;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;

/**
 * Authentication controller.
 *
 * Input validation, authorization, and response formatting ONLY.
 * No business logic — all delegated to AuthService.
 *
 * @OA\Tag(name="Auth", description="Authentication endpoints")
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     tags={"Auth"},
     *     summary="Register a new user and obtain a JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id","name","email","password"},
     *             @OA\Property(property="tenant_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered and JWT token returned"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'   => ['required', 'integer', 'exists:tenants,id'],
            'name'        => ['required', 'string', 'max:255'],
            'email'       => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('tenant_id', $request->input('tenant_id')),
            ],
            'password'    => ['required', 'string', 'min:8'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $dto   = RegisterDTO::fromArray($validated);
        $token = $this->authService->register($dto);

        return ApiResponse::success(
            ['access_token' => $token, 'token_type' => 'bearer'],
            'Registration successful.',
            201
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Auth"},
     *     summary="Login and obtain a JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="JWT token returned"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $dto = LoginDTO::fromArray($validated);

        $token = $this->authService->login($dto);

        return ApiResponse::success(
            ['access_token' => $token, 'token_type' => 'bearer'],
            'Login successful.'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Auth"},
     *     summary="Logout and invalidate the current JWT token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logged out successfully")
     * )
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ApiResponse::success(message: 'Logged out successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     tags={"Auth"},
     *     summary="Refresh the JWT token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="New JWT token returned")
     * )
     */
    public function refresh(): JsonResponse
    {
        $token = $this->authService->refresh();

        return ApiResponse::success(
            ['access_token' => $token, 'token_type' => 'bearer'],
            'Token refreshed.'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     tags={"Auth"},
     *     summary="Get the currently authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Authenticated user data")
     * )
     */
    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return ApiResponse::success($user->only(['id', 'name', 'email', 'tenant_id']));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/password/change",
     *     tags={"Auth"},
     *     summary="Change the password of the currently authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","new_password","new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password"),
     *             @OA\Property(property="new_password", type="string", format="password", minLength=8),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password changed successfully"),
     *     @OA\Response(response=401, description="Unauthenticated or current password incorrect"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->authService->changePassword($validated['current_password'], $validated['new_password']);

        return ApiResponse::success(message: 'Password changed successfully.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/auth/profile",
     *     tags={"Auth"},
     *     summary="Update the profile of the currently authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", nullable=true, example="Jane Doe"),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="jane@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Profile updated"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $user = $this->authService->updateProfile($validated);

        return ApiResponse::success($user->only(['id', 'name', 'email', 'tenant_id']), 'Profile updated.');
    }
}
