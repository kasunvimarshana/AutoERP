<?php

namespace Modules\IAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\IAM\DTOs\LoginDTO;
use Modules\IAM\DTOs\RegisterDTO;
use Modules\IAM\Http\Requests\ForgotPasswordRequest;
use Modules\IAM\Http\Requests\LoginRequest;
use Modules\IAM\Http\Requests\RegisterRequest;
use Modules\IAM\Http\Requests\ResetPasswordRequest;
use Modules\IAM\Services\AuthService;

class AuthController extends BaseController
{
    public function __construct(private AuthService $authService) {}

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="User login",
     *     description="Authenticate user credentials and generate access token",
     *     operationId="authLogin",
     *     tags={"IAM-Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials",
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
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
     *                 @OA\Property(property="token", type="string", example="1|abcdef123456..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2024-02-15T10:30:00Z"),
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $dto = new LoginDTO($request->validated());

            $result = $this->authService->login(
                $dto,
                $request->ip(),
                $request->userAgent() ?? 'Unknown'
            );

            return $this->success($result, 'Login successful');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Login failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Login failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="User registration",
     *     description="Register a new user account",
     *     operationId="authRegister",
     *     tags={"IAM-Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful. Please check your email to verify your account."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $dto = new RegisterDTO($request->validated());
            $user = $this->authService->register($dto);

            return $this->created(
                ['user' => $user],
                'Registration successful. Please check your email to verify your account.'
            );
        } catch (\Exception $e) {
            return $this->error('Registration failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="User logout",
     *     description="Logout current user and revoke access token",
     *     operationId="authLogout",
     *     tags={"IAM-Auth"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return $this->success(null, 'Logout successful');
        } catch (\Exception $e) {
            return $this->error('Logout failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout-all",
     *     summary="Logout from all devices",
     *     description="Logout user from all devices by revoking all access tokens",
     *     operationId="authLogoutAllDevices",
     *     tags={"IAM-Auth"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out from all devices",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out from all devices")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function logoutAllDevices(Request $request): JsonResponse
    {
        try {
            $this->authService->logoutAllDevices($request->user());

            return $this->success(null, 'Logged out from all devices');
        } catch (\Exception $e) {
            return $this->error('Logout failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     summary="Refresh access token",
     *     description="Generate a new access token for the authenticated user",
     *     operationId="authRefresh",
     *     tags={"IAM-Auth"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         description="Token refresh options",
     *         @OA\JsonContent(
     *             @OA\Property(property="remember", type="boolean", example=false, description="Extend token expiration to 30 days")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token refreshed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="1|abcdef123456..."),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2024-02-15T10:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $remember = $request->boolean('remember', false);
            $token = $this->authService->refreshToken($request->user(), $remember);

            return $this->success([
                'token' => $token,
                'expires_at' => $remember ? now()->addDays(30) : now()->addDay(),
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->error('Token refresh failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/forgot-password",
     *     summary="Request password reset",
     *     description="Send password reset link to user's email",
     *     operationId="authForgotPassword",
     *     tags={"IAM-Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User email for password reset",
     *         @OA\JsonContent(ref="#/components/schemas/ForgotPasswordRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reset link sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password reset link sent to your email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $message = $this->authService->sendPasswordResetLink($request->email);

            return $this->success(null, $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Failed to send reset link', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to send reset link: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/reset-password",
     *     summary="Reset password",
     *     description="Reset user password using the token sent via email",
     *     operationId="authResetPassword",
     *     tags={"IAM-Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Password reset data",
     *         @OA\JsonContent(ref="#/components/schemas/ResetPasswordRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password reset successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or invalid token",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $message = $this->authService->resetPassword(
                $request->email,
                $request->password,
                $request->token
            );

            return $this->success(null, $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Password reset failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Password reset failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Get authenticated user",
     *     description="Retrieve the currently authenticated user's profile with roles and permissions",
     *     operationId="authMe",
     *     tags={"IAM-Auth"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
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
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('roles', 'permissions');

            return $this->success(['user' => $user]);
        } catch (\Exception $e) {
            return $this->error('Failed to get user data: '.$e->getMessage(), 500);
        }
    }
}
