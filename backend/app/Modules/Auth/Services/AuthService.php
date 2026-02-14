<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Events\PasswordReset;
use App\Modules\Auth\Events\UserLoggedIn;
use App\Modules\Auth\Events\UserLoggedOut;
use App\Modules\Auth\Events\UserRegistered;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset as LaravelPasswordReset;
use Exception;

class AuthService
{
    public function register(RegisterDTO $dto): array
    {
        try {
            return DB::transaction(function () use ($dto) {
                $user = User::create([
                    'name' => $dto->name,
                    'email' => $dto->email,
                    'password' => Hash::make($dto->password),
                    'tenant_id' => $dto->tenant_id,
                    'status' => 'active',
                ]);

                $token = $user->createToken('auth_token')->plainTextToken;

                event(new UserRegistered($user));

                return [
                    'user' => $user->load(['tenant', 'roles', 'permissions']),
                    'token' => $token,
                ];
            });
        } catch (Exception $e) {
            Log::error('Registration failed: ' . $e->getMessage(), ['dto' => $dto->toArray()]);
            throw new Exception('Registration failed. Please try again.');
        }
    }

    public function login(string $email, string $password): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user || !Hash::check($password, $user->password)) {
                throw new Exception('Invalid credentials');
            }

            if (!$user->isActive()) {
                throw new Exception('Account is inactive');
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            event(new UserLoggedIn($user));

            return [
                'user' => $user->load(['tenant', 'roles', 'permissions']),
                'token' => $token,
            ];
        } catch (Exception $e) {
            if ($e->getMessage() === 'Invalid credentials' || $e->getMessage() === 'Account is inactive') {
                throw $e;
            }
            Log::error('Login failed: ' . $e->getMessage(), ['email' => $email]);
            throw new Exception('Login failed. Please try again.');
        }
    }

    public function logout(User $user): bool
    {
        try {
            $user->currentAccessToken()->delete();

            event(new UserLoggedOut($user));

            return true;
        } catch (Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage(), ['user_id' => $user->id]);
            throw new Exception('Logout failed. Please try again.');
        }
    }

    public function forgotPassword(string $email): bool
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                return true;
            }

            $status = Password::sendResetLink(['email' => $email]);

            if ($status !== Password::RESET_LINK_SENT) {
                Log::error('Password reset link sending failed', ['email' => $email, 'status' => $status]);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage(), ['email' => $email]);
            return true;
        }
    }

    public function resetPassword(string $email, string $token, string $password): bool
    {
        try {
            return DB::transaction(function () use ($email, $token, $password) {
                $status = Password::reset(
                    [
                        'email' => $email,
                        'password' => $password,
                        'password_confirmation' => $password,
                        'token' => $token,
                    ],
                    function (User $user, string $password) {
                        $user->forceFill([
                            'password' => Hash::make($password),
                        ])->save();

                        $user->tokens()->delete();

                        event(new LaravelPasswordReset($user));
                        event(new PasswordReset($user));
                    }
                );

                if ($status !== Password::PASSWORD_RESET) {
                    throw new Exception('Failed to reset password');
                }

                return true;
            });
        } catch (Exception $e) {
            Log::error('Password reset failed: ' . $e->getMessage(), ['email' => $email]);
            throw new Exception('Password reset failed. Please try again or request a new reset link.');
        }
    }

    public function updateProfile(User $user, array $data): bool
    {
        try {
            return DB::transaction(function () use ($user, $data) {
                $updateData = [];

                if (isset($data['name'])) {
                    $updateData['name'] = $data['name'];
                }

                if (isset($data['email'])) {
                    $updateData['email'] = $data['email'];
                }

                if (isset($data['phone'])) {
                    $updateData['phone'] = $data['phone'];
                }

                $user->update($updateData);

                return true;
            });
        } catch (Exception $e) {
            Log::error('Profile update failed: ' . $e->getMessage(), ['user_id' => $user->id]);
            throw new Exception('Profile update failed. Please try again.');
        }
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        try {
            return DB::transaction(function () use ($user, $currentPassword, $newPassword) {
                if (!Hash::check($currentPassword, $user->password)) {
                    throw new Exception('Current password is incorrect');
                }

                $user->update([
                    'password' => Hash::make($newPassword),
                ]);

                $user->tokens()->delete();

                return true;
            });
        } catch (Exception $e) {
            if ($e->getMessage() === 'Current password is incorrect') {
                throw $e;
            }
            Log::error('Password update failed: ' . $e->getMessage(), ['user_id' => $user->id]);
            throw new Exception('Password update failed. Please try again.');
        }
    }
}
