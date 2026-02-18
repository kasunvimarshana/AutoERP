<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Authenticate a user and return access token.
     *
     * @param array $credentials
     * @return array{user: User, token: string}
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $tenant = app('tenant');
        
        if (!$tenant) {
            throw ValidationException::withMessages([
                'tenant' => ['Tenant context is required'],
            ]);
        }

        // Find user by email within tenant
        $user = User::where('tenant_id', $tenant->id)
            ->where('email', $credentials['email'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate API token
        $token = $user->createToken(
            name: $credentials['device_name'] ?? 'web',
            expiresAt: now()->addDays(30)
        )->plainTextToken;

        return [
            'user' => $user->load('roles.permissions'),
            'token' => $token,
        ];
    }

    /**
     * Register a new user.
     *
     * @param array $data
     * @return User
     */
    public function register(array $data): User
    {
        $tenant = app('tenant');
        
        if (!$tenant) {
            throw ValidationException::withMessages([
                'tenant' => ['Tenant context is required'],
            ]);
        }

        // Check if email already exists for this tenant
        if (User::where('tenant_id', $tenant->id)->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Assign default role if provided
        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        return $user;
    }

    /**
     * Logout user by revoking all tokens.
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        $user->tokens()->delete();
        return true;
    }

    /**
     * Get authenticated user with relationships.
     *
     * @param User $user
     * @return User
     */
    public function getAuthenticatedUser(User $user): User
    {
        return $user->load(['roles.permissions', 'tenant']);
    }

    /**
     * Refresh user's access token.
     *
     * @param User $user
     * @param string $deviceName
     * @return string
     */
    public function refreshToken(User $user, string $deviceName = 'web'): string
    {
        // Delete current token
        $user->currentAccessToken()->delete();

        // Create new token
        return $user->createToken(
            name: $deviceName,
            expiresAt: now()->addDays(30)
        )->plainTextToken;
    }
}
