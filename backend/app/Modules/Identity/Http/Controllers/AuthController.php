<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Identity\Requests\LoginRequest;
use App\Modules\Identity\Requests\RegisterRequest;
use App\Modules\Identity\Resources\UserResource;
use App\Modules\Identity\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Authentication Controller
 * 
 * Handles user authentication operations including login, registration, and logout
 * 
 * @package App\Modules\Identity\Http\Controllers
 */
class AuthController extends BaseController
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());
        
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return $this->created([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'User registered successfully');
    }

    /**
     * Authenticate user and generate token
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        
        if (!Auth::attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }
        
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return $this->success([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
     * Logout user and revoke token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        
        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user information
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return $this->resource(new UserResource($request->user()));
    }
}
