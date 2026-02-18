<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\IAM\Http\Requests\LoginRequest;
use Modules\IAM\Http\Requests\RegisterRequest;
use Modules\IAM\Http\Requests\PasswordResetRequest;
use Modules\IAM\Http\Requests\PasswordResetInitiateRequest;
use Modules\IAM\Http\Resources\UserResource;
use Modules\IAM\Services\AuthService;
use Modules\IAM\Exceptions\AuthenticationException;

/**
 * Authentication Controller
 * 
 * Handles user authentication operations including login, register, logout,
 * token refresh, and password reset with JWT-based stateless authentication.
 */
class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}
    
    /**
     * Login user and generate JWT tokens
     * 
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Authenticate user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="tenant_id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=429, description="Too many attempts")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->validated(),
                $request->input('tenant_id')
            );
            
            return response()->json([
                'message' => 'Login successful',
                'user' => new UserResource($result['user']),
                'access_token' => $result['tokens']['access_token'],
                'refresh_token' => $result['tokens']['refresh_token'],
                'token_type' => $result['tokens']['token_type'],
                'expires_in' => $result['tokens']['expires_in'],
                'tenant_id' => $result['tokens']['tenant_id'],
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
    
    /**
     * Register new user
     * 
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Register new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password"),
     *             @OA\Property(property="tenant_id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Registration successful"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());
            
            return response()->json([
                'message' => 'Registration successful',
                'user' => new UserResource($result['user']),
                'access_token' => $result['tokens']['access_token'],
                'refresh_token' => $result['tokens']['refresh_token'],
                'token_type' => $result['tokens']['token_type'],
                'expires_in' => $result['tokens']['expires_in'],
                'tenant_id' => $result['tokens']['tenant_id'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Logout user and revoke current token
     * 
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logout successful"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(): JsonResponse
    {
        $user = request()->user();
        $token = request()->bearerToken();
        
        if (!$user || !$token) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }
        
        $this->authService->logout($user, $token);
        
        return response()->json([
            'message' => 'Logout successful',
        ]);
    }
    
    /**
     * Logout from all devices
     * 
     * @OA\Post(
     *     path="/api/v1/auth/logout-all",
     *     summary="Logout from all devices",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logged out from all devices"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logoutAll(): JsonResponse
    {
        $user = request()->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }
        
        $this->authService->logoutAllDevices($user);
        
        return response()->json([
            'message' => 'Logged out from all devices successfully',
        ]);
    }
    
    /**
     * Refresh access token
     * 
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     summary="Refresh access token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Token refreshed"),
     *     @OA\Response(response=401, description="Invalid refresh token")
     * )
     */
    public function refresh(): JsonResponse
    {
        $refreshToken = request()->input('refresh_token');
        
        if (!$refreshToken) {
            return response()->json([
                'message' => 'Refresh token is required',
            ], 400);
        }
        
        $tokens = $this->authService->refresh($refreshToken);
        
        if (!$tokens) {
            return response()->json([
                'message' => 'Invalid or expired refresh token',
            ], 401);
        }
        
        return response()->json([
            'message' => 'Token refreshed successfully',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'tenant_id' => $tokens['tenant_id'],
        ]);
    }
    
    /**
     * Get authenticated user
     * 
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="Get authenticated user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="User retrieved"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(): JsonResponse
    {
        $user = request()->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }
        
        return response()->json([
            'user' => new UserResource($user),
        ]);
    }
    
    /**
     * Initiate password reset
     * 
     * @OA\Post(
     *     path="/api/v1/auth/password/reset",
     *     summary="Initiate password reset",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Reset email sent"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function initiatePasswordReset(PasswordResetInitiateRequest $request): JsonResponse
    {
        $this->authService->initiatePasswordReset($request->input('email'));
        
        return response()->json([
            'message' => 'If your email exists in our system, you will receive a password reset link.',
        ]);
    }
    
    /**
     * Reset password
     * 
     * @OA\Post(
     *     path="/api/v1/auth/password/reset/confirm",
     *     summary="Reset password with token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","token","password","password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset successful"),
     *     @OA\Response(response=400, description="Invalid or expired token")
     * )
     */
    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        try {
            $this->authService->resetPassword(
                $request->input('email'),
                $request->input('token'),
                $request->input('password')
            );
            
            return response()->json([
                'message' => 'Password reset successful. Please login with your new password.',
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
