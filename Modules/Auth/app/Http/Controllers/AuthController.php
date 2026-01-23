<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\RegisterRequest;
use Modules\Auth\Requests\ForgotPasswordRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Resources\AuthResource;

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
     *
     * @param AuthService $authService
     */
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->createdResponse(
            new AuthResource($result),
            __('auth::messages.registration_successful')
        );
    }

    /**
     * Login user and issue token
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->successResponse(
            new AuthResource($result),
            __('auth::messages.login_successful')
        );
    }

    /**
     * Logout current user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(
            null,
            __('auth::messages.logout_successful')
        );
    }

    /**
     * Logout from all devices
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return $this->successResponse(
            null,
            __('auth::messages.logout_all_successful')
        );
    }

    /**
     * Get current authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new AuthResource([
                'user' => $request->user()->load(['roles', 'permissions']),
                'token' => null,
            ]),
            __('auth::messages.user_retrieved')
        );
    }

    /**
     * Refresh authentication token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refreshToken($request->user());

        return $this->successResponse(
            new AuthResource($result),
            __('auth::messages.token_refreshed')
        );
    }

    /**
     * Request password reset
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->validated());

        return $this->successResponse(
            null,
            __('auth::messages.password_reset_link_sent')
        );
    }

    /**
     * Reset password
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->validated());

        return $this->successResponse(
            null,
            __('auth::messages.password_reset_successful')
        );
    }

    /**
     * Verify email
     *
     * @param Request $request
     * @param string $id
     * @param string $hash
     * @return JsonResponse
     */
    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        $result = $this->authService->verifyEmail($id, $hash);

        if (!$result) {
            return $this->errorResponse(
                __('auth::messages.email_verification_failed'),
                422
            );
        }

        return $this->successResponse(
            null,
            __('auth::messages.email_verified')
        );
    }

    /**
     * Resend email verification
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendEmailVerification(Request $request): JsonResponse
    {
        $this->authService->resendEmailVerification($request->user());

        return $this->successResponse(
            null,
            __('auth::messages.verification_link_sent')
        );
    }
}
