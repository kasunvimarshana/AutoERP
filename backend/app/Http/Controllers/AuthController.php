<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            // Get tenant from request to use in custom validation rule
            $tenantSlug = $request->input('tenant_slug');
            $tenant = $tenantSlug ? Tenant::where('slug', $tenantSlug)->first() : null;

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    function ($attribute, $value, $fail) use ($tenant) {
                        if ($tenant) {
                            $existingUser = User::where('email', $value)
                                ->where('tenant_id', $tenant->id)
                                ->first();
                            if ($existingUser) {
                                $fail('The email has already been registered for this tenant.');
                            }
                        }
                    },
                ],
                'password' => 'required|string|min:8|confirmed',
                'tenant_slug' => 'required|string|exists:tenants,slug',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            // Get tenant
            if (! $tenant) {
                $tenant = Tenant::where('slug', $request->tenant_slug)->first();
            }

            if (! $tenant || ! $tenant->is_active) {
                return $this->errorResponse('Invalid or inactive tenant', 400);
            }

            // Create user
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => true,
            ]);

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
            ], 'User registered successfully', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed: '.$e->getMessage(), null, 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
                'tenant_slug' => 'required|string|exists:tenants,slug',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            // Get tenant
            $tenant = Tenant::where('slug', $request->tenant_slug)->first();

            if (! $tenant || ! $tenant->is_active) {
                return $this->errorResponse('Invalid or inactive tenant', null, 400);
            }

            // Find user by email and tenant
            $user = User::where('email', $request->email)
                ->where('tenant_id', $tenant->id)
                ->first();

            if (! $user) {
                return $this->errorResponse('Invalid credentials', null, 401);
            }

            if (! $user->is_active) {
                return $this->errorResponse('User account is inactive', null, 403);
            }

            // Check password
            if (! Hash::check($request->password, $user->password)) {
                return $this->errorResponse('Invalid credentials', null, 401);
            }

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->successResponse([
                'user' => $user->load('roles.permissions'),
                'token' => $token,
            ], 'Login successful');

        } catch (\Exception $e) {
            return $this->errorResponse('Login failed: '.$e->getMessage(), null, 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke current token
            $token = $request->user()->currentAccessToken();
            if ($token) {
                $token->delete();
            }

            return $this->successResponse(null, 'Logged out successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed: '.$e->getMessage(), null, 500);
        }
    }

    /**
     * Get current authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['roles.permissions', 'tenant']);

            return $this->successResponse($user);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch user: '.$e->getMessage(), null, 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $token = $request->user()->createToken('auth-token')->plainTextToken;

            return $this->successResponse([
                'token' => $token,
            ], 'Token refreshed successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Token refresh failed: '.$e->getMessage(), null, 500);
        }
    }

    /**
     * Get current authenticated user profile (alias for user)
     */
    public function me(Request $request): JsonResponse
    {
        return $this->user($request);
    }

    /**
     * Update user profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $user = $request->user();

            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                // Check if email is unique for the tenant
                $existingUser = User::where('email', $request->email)
                    ->where('tenant_id', $user->tenant_id)
                    ->where('id', '!=', $user->id)
                    ->first();

                if ($existingUser) {
                    return $this->errorResponse('Email already exists for this tenant', null, 422);
                }

                $user->email = $request->email;
            }

            $user->save();

            return $this->successResponse($user->load(['roles.permissions', 'tenant']), 'Profile updated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update profile: '.$e->getMessage(), null, 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $user = $request->user();

            // Check current password
            if (! Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Current password is incorrect', null, 422);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->save();

            return $this->successResponse(null, 'Password changed successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to change password: '.$e->getMessage(), null, 500);
        }
    }
}
