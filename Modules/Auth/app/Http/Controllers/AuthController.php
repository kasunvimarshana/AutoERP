<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Requests\ForgotPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\RegisterRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\Auth\Resources\AuthResource;
use Modules\Auth\Services\AuthService;
use OpenApi\Attributes as OA;

/**
 * Authentication Controller
 *
 * Handles all authentication operations including login, register, logout,
 * password reset, and token management
 */
class AuthController extends Controller
{
    /**
     * AuthController constructor
     */

    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Register a new user
     *
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Register a new user",
     *     description="Create a new user account with email and password . Optionally assign a role during registration . ",
     *     operationId="register",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example . com", description="User's email address (must be unique)"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePassword123!", description="User's password (minimum 8 characters)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePassword123!", description="Password confirmation (must match password)"),
     *             @OA\Property(property="role", type="string", example="user", description="Optional role to assign (must exist in roles table)", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(
     *                     property="token",
     *                     type="object",
     *                     @OA\Property(property="access_token", type="string", example="1|abc123..."),
     *                     @OA\Property(property="token_type", type="string", example="Bearer"),
     *                     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->createdResponse(
            new AuthResource($result),
            __('auth::messages . registration_successful')
        );
    }

    /**
     * Login user and issue token
     *
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Login user",
     *     description="Authenticate user with email and password, and issue a bearer token for API access",
     *     operationId="login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example . com", description="User's email address"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePassword123!", description="User's password"),
     *             @OA\Property(property="revoke_other_tokens", type="boolean", example=false, description="Revoke all other tokens for this user", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(
     *                     property="token",
     *                     type="object",
     *                     @OA\Property(property="access_token", type="string", example="1|abc123..."),
     *                     @OA\Property(property="token_type", type="string", example="Bearer"),
     *                     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->successResponse(
            new AuthResource($result),
            __('auth::messages . login_successful')
        );
    }

    /**
     * Logout current user
     *
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Logout current session",
     *     description="Revoke the current authentication token and logout the user from this session",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout successful"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated . ")
     *         )
     *     )
     * )
     */

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(
            null,
            __('auth::messages . logout_successful')
        );
    }

    /**
     * Logout from all devices
     *
     * @OA\Post(
     *     path="/api/v1/auth/logout-all",
     *     summary="Logout from all devices",
     *     description="Revoke all authentication tokens for the current user and logout from all sessions/devices",
     *     operationId="logoutAll",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out from all devices successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out from all devices"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated . ")
     *         )
     *     )
     * )
     */

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return $this->successResponse(
            null,
            __('auth::messages . logout_all_successful')
        );
    }

    /**
     * Get current authenticated user
     *
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="Get authenticated user",
     *     description="Retrieve the current authenticated user's information including roles and permissions",
     *     operationId="me",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated . ")
     *         )
     *     )
     * )
     */

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new AuthResource([
                'user' => $request->user()->load(['roles', 'permissions']),
                'token' => null,
            ]),
            __('auth::messages . user_retrieved')
        );
    }

    /**
     * Refresh authentication token
     *
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     summary="Refresh authentication token",
     *     description="Generate a new bearer token while revoking the current one for security",
     *     operationId="refresh",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token refreshed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(
     *                     property="token",
     *                     type="object",
     *                     @OA\Property(property="access_token", type="string", example="2|xyz789..."),
     *                     @OA\Property(property="token_type", type="string", example="Bearer"),
     *                     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated . ")
     *         )
     *     )
     * )
     */

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refreshToken($request->user());

        return $this->successResponse(
            new AuthResource($result),
            __('auth::messages . token_refreshed')
        );
    }

    /**
     * Request password reset
     *
     * @OA\Post(
     *     path="/api/v1/auth/forgot-password",
     *     summary="Request password reset",
     *     description="Send a password reset link to the user's email address",
     *     operationId="forgotPassword",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Email address for password reset",
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example . com", description="User's registered email address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password reset link sent to your email"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found with this email")
     *         )
     *     )
     * )
     */

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->validated());

        return $this->successResponse(
            null,
            __('auth::messages . password_reset_link_sent')
        );
    }

    /**
     * Reset password
     *
     * @OA\Post(
     *     path="/api/v1/auth/reset-password",
     *     summary="Reset password",
     *     description="Reset user password using the token received via email",
     *     operationId="resetPassword",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Password reset data",
     *         @OA\JsonContent(
     *             required={"email", "token", "password", "password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example . com", description="User's email address"),
     *             @OA\Property(property="token", type="string", example="abc123token...", description="Password reset token from email"),
     *             @OA\Property(property="password", type="string", format="password", example="NewSecurePassword123!", description="New password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewSecurePassword123!", description="Password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password has been reset successfully"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired token",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid or expired password reset token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->validated());

        return $this->successResponse(
            null,
            __('auth::messages . password_reset_successful')
        );
    }

    /**
     * Verify email
     *
     * @OA\Get(
     *     path="/api/v1/auth/verify-email/{id}/{hash}",
     *     summary="Verify email address",
     *     description="Verify user's email address using the verification link sent via email",
     *     operationId="verifyEmail",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         required=true,
     *         description="Verification hash",
     *         @OA\Schema(type="string", example="abc123hash...")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email verified successfully"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Verification failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email verification failed . Invalid or expired link . ")
     *         )
     *     )
     * )
     */

    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        $result = $this->authService->verifyEmail($id, $hash);

        if (! $result) {
            return $this->errorResponse(
                __('auth::messages . email_verification_failed'),
                422
            );
        }

        return $this->successResponse(
            null,
            __('auth::messages . email_verified')
        );
    }

    /**
     * Resend email verification
     *
     * @OA\Post(
     *     path="/api/v1/auth/resend-verification",
     *     summary="Resend email verification",
     *     description="Resend the email verification link to the authenticated user",
     *     operationId="resendEmailVerification",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Verification link sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verification link sent to your email"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated . ")
     *         )
     *     )
     * )
     */

    public function resendEmailVerification(Request $request): JsonResponse
    {
        $this->authService->resendEmailVerification($request->user());

        return $this->successResponse(
            null,
            __('auth::messages . verification_link_sent')
        );
    }
}
