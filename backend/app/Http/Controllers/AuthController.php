<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    /**
     * Register a new user under the current tenant.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $tenant = app('current_tenant');

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'phone'     => $request->phone,
            'status'    => 'active',
        ]);

        $user->assignRole($request->role ?? 'user');

        $token = $user->createToken('auth_token')->accessToken;

        return $this->createdResponse([
            'user'         => new UserResource($user),
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 'User registered successfully');
    }

    /**
     * Authenticate user and issue Passport token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $tenant = app('current_tenant');

        $user = User::where('email', $request->email)
                    ->where('tenant_id', $tenant->id)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        if (!$user->isActive()) {
            return $this->errorResponse('Account is inactive', 403);
        }

        // Revoke old tokens if single-session mode
        if (config('tenant_runtime.settings.single_session', false)) {
            $user->tokens()->delete();
        }

        $tokenResult  = $user->createToken('auth_token');
        $accessToken  = $tokenResult->accessToken;
        $refreshToken = $tokenResult->token;

        $user->recordLogin();

        return $this->successResponse([
            'user'         => new UserResource($user->load('roles')),
            'access_token' => $accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => now()->addDays((int) config('passport.token_expire_days', 15))->toIso8601String(),
        ], 'Login successful');
    }

    /**
     * Logout — revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Revoke all tokens for the authenticated user.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->successResponse(null, 'All sessions terminated');
    }

    /**
     * Return authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UserResource($request->user()->load(['roles', 'permissions', 'tenant']))
        );
    }

    /**
     * SSO token introspection / validation endpoint.
     */
    public function introspect(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        return $this->successResponse([
            'active'    => true,
            'user_id'   => $user->id,
            'tenant_id' => $user->tenant_id,
            'email'     => $user->email,
            'roles'     => $user->getRoleNames(),
            'scopes'    => $request->user()->token()->scopes ?? [],
            'expires_at' => $user->token()->expires_at ?? null,
        ]);
    }

    /**
     * Exchange a valid token for a new one (refresh-like flow).
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current token and issue a new one
        $user->token()->revoke();
        $newToken = $user->createToken('auth_token')->accessToken;

        return $this->successResponse([
            'access_token' => $newToken,
            'token_type'   => 'Bearer',
            'expires_at'   => now()->addDays((int) config('passport.token_expire_days', 15))->toIso8601String(),
        ], 'Token refreshed');
    }
}
