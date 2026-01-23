<?php

namespace App\Modules\AuthManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\AuthManagement\Services\AuthService;
use App\Modules\AuthManagement\Http\Requests\RegisterRequest;
use App\Modules\AuthManagement\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends BaseController
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());
            
            return $this->created([
                'user' => $result['user'],
                'token' => $result['token'],
            ], 'Registration successful');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());
            
            return $this->success([
                'user' => $result['user'],
                'token' => $result['token'],
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());
            return $this->success(null, 'Logout successful');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            return $this->success($request->user());
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $token = $this->authService->refreshToken($request->user());
            return $this->success(['token' => $token], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $this->authService->changePassword(
                $request->user(),
                $request->input('current_password'),
                $request->input('new_password')
            );

            return $this->success(null, 'Password changed successfully. Please login again.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function requestPasswordReset(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $this->authService->requestPasswordReset($request->input('email'));
            
            return $this->success(null, 'If an account exists with this email, you will receive password reset instructions.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $result = $this->authService->resetPassword(
                $request->input('email'),
                $request->input('token'),
                $request->input('password')
            );

            if ($result) {
                return $this->success(null, 'Password reset successfully. Please login.');
            }

            return $this->error('Invalid or expired reset token', 400);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
