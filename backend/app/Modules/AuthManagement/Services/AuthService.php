<?php

namespace App\Modules\AuthManagement\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        try {
            $user = User::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'user',
                'status' => 'active',
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            Log::info('User registered', ['user_id' => $user->id, 'email' => $user->email]);

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            Log::error('User registration failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive. Please contact support.'],
            ]);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        Log::info('User logged in', ['user_id' => $user->id, 'email' => $user->email]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        try {
            $user->currentAccessToken()->delete();
            Log::info('User logged out', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('Logout failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function refreshToken(User $user): string
    {
        try {
            // Delete old tokens
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('auth-token')->plainTextToken;

            Log::info('Token refreshed', ['user_id' => $user->id]);

            return $token;
        } catch (\Exception $e) {
            Log::error('Token refresh failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password is incorrect.'],
            ]);
        }

        try {
            $user->update(['password' => Hash::make($newPassword)]);
            
            // Delete all tokens to force re-login
            $user->tokens()->delete();

            Log::info('Password changed', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('Password change failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function requestPasswordReset(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Don't reveal if user exists
            return true;
        }

        // TODO: Send password reset email
        Log::info('Password reset requested', ['email' => $email]);

        return true;
    }

    public function resetPassword(string $email, string $token, string $password): bool
    {
        // TODO: Implement password reset with token validation
        // This is a placeholder implementation
        
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        try {
            $user->update(['password' => Hash::make($password)]);
            $user->tokens()->delete();

            Log::info('Password reset', ['user_id' => $user->id]);

            return true;
        } catch (\Exception $e) {
            Log::error('Password reset failed', ['email' => $email, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
