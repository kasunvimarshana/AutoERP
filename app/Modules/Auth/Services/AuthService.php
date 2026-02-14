<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Authenticate user and generate token
     */
    public function login(array $credentials): array
    {
        try {
            if (! Auth::attempt($credentials)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = Auth::user();
            $token = $user->createToken('auth-token')->plainTextToken;

            Log::info("User {$user->id} logged in successfully");

            return [
                'success' => true,
                'token' => $token,
                'user' => $user->load('tenant'),
                'tenant' => $user->tenant,
            ];
        } catch (\Exception $e) {
            Log::error('Login error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Register new user
     */
    public function register(array $data): array
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'tenant_id' => $data['tenant_id'],
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            DB::commit();

            Log::info("User {$user->id} registered successfully");

            return [
                'success' => true,
                'token' => $token,
                'user' => $user->load('tenant'),
                'message' => 'User registered successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Logout user by deleting current access token
     */
    public function logout(User $user): array
    {
        try {
            $user->currentAccessToken()->delete();

            Log::info("User {$user->id} logged out successfully");

            return [
                'success' => true,
                'message' => 'Logged out successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Logout error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get authenticated user details
     */
    public function me(User $user): array
    {
        try {
            return [
                'success' => true,
                'user' => $user->load('tenant'),
                'tenant' => $user->tenant,
            ];
        } catch (\Exception $e) {
            Log::error('Me error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Refresh user token
     */
    public function refresh(User $user): array
    {
        DB::beginTransaction();

        try {
            $user->tokens()->delete();
            $token = $user->createToken('auth-token')->plainTextToken;

            DB::commit();

            Log::info("User {$user->id} token refreshed successfully");

            return [
                'success' => true,
                'token' => $token,
                'user' => $user->load('tenant'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Token refresh error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(string $email): array
    {
        try {
            $status = Password::sendResetLink(['email' => $email]);

            if ($status === Password::RESET_LINK_SENT) {
                Log::info("Password reset link sent to {$email}");

                return [
                    'success' => true,
                    'message' => 'Password reset link sent to your email',
                ];
            }

            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        } catch (\Exception $e) {
            Log::error('Forgot password error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(array $data): array
    {
        try {
            $status = Password::reset(
                [
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'password_confirmation' => $data['password_confirmation'],
                    'token' => $data['token'],
                ],
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->save();

                    $user->tokens()->delete();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                Log::info("Password reset successfully for {$data['email']}");

                return [
                    'success' => true,
                    'message' => 'Password has been reset successfully',
                ];
            }

            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        } catch (\Exception $e) {
            Log::error('Reset password error: '.$e->getMessage());
            throw $e;
        }
    }
}
