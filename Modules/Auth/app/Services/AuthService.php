<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Repositories\AuthRepository;

/**
 * Authentication Service
 *
 * Handles all authentication business logic including user registration,
 * login, logout, password reset, and email verification
 */
class AuthService extends BaseService
{
    /**
     * AuthService constructor
     */
    public function __construct(
        AuthRepository $repository
    ) {
        parent::__construct($repository);
    }

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        // Hash password
        $data['password'] = Hash::make($data['password']);

        // Set default status
        $data['email_verified_at'] = null;

        // Create user
        $user = $this->repository->create($data);

        // Assign default role
        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        } else {
            $user->assignRole('user'); // Default role
        }

        // Fire registered event
        event(new Registered($user));

        // Log registration
        AuthAuditLogger::logSuccessfulRegistration($user);

        // Generate token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load(['roles', 'permissions']),
            'token' => $token,
        ];
    }

    /**
     * Login user
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        // Find user by email
        $user = $this->repository->findByEmail($credentials['email']);

        // Check if user exists and password is correct
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            AuthAuditLogger::logFailedLogin($credentials['email'], 'invalid_credentials');
            throw ValidationException::withMessages([
                'email' => [__('auth::messages.invalid_credentials')],
            ]);
        }

        // Check if user is active (if is_active attribute exists)
        if (isset($user->is_active) && ! $user->is_active) {
            AuthAuditLogger::logFailedLogin($credentials['email'], 'account_inactive');
            throw ValidationException::withMessages([
                'email' => [__('auth::messages.account_inactive')],
            ]);
        }

        // Revoke old tokens if specified
        if ($credentials['revoke_other_tokens'] ?? false) {
            $user->tokens()->delete();
        }

        // Generate new token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log login event
        AuthAuditLogger::logSuccessfulLogin($user);

        return [
            'user' => $user->load(['roles', 'permissions']),
            'token' => $token,
        ];
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(User $user): void
    {
        // Revoke current token
        $user->currentAccessToken()->delete();

        // Log logout event
        AuthAuditLogger::logLogout($user, false);
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(User $user): void
    {
        // Revoke all tokens
        $user->tokens()->delete();

        // Log logout event
        AuthAuditLogger::logLogout($user, true);
    }

    /**
     * Refresh user token
     */
    public function refreshToken(User $user): array
    {
        // Revoke current token
        $user->currentAccessToken()->delete();

        // Generate new token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log token refresh
        AuthAuditLogger::logTokenRefresh($user);

        return [
            'user' => $user->load(['roles', 'permissions']),
            'token' => $token,
        ];
    }

    /**
     * Send password reset link
     *
     * @throws ValidationException
     */
    public function sendPasswordResetLink(array $data): void
    {
        $status = Password::sendResetLink(['email' => $data['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        // Log password reset request
        AuthAuditLogger::logPasswordResetRequest($data['email']);
    }

    /**
     * Reset password
     *
     * @throws ValidationException
     */
    public function resetPassword(array $data): void
    {
        $status = Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
                'token' => $data['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all tokens
                $user->tokens()->delete();

                event(new PasswordReset($user));

                // Log password reset
                AuthAuditLogger::logPasswordReset($user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    /**
     * Verify email
     */
    public function verifyEmail(string $id, string $hash): bool
    {
        $user = $this->repository->find((int) $id);

        if (! $user) {
            return false;
        }

        // Check if hash matches
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return false;
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        // Mark as verified
        $user->markEmailAsVerified();

        event(new Verified($user));

        // Log email verification
        AuthAuditLogger::logEmailVerification($user);

        return true;
    }

    /**
     * Resend email verification
     */
    public function resendEmailVerification(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
    }
}
