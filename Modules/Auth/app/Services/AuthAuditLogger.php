<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Authentication Audit Logger
 * 
 * Handles structured, immutable logging of authentication events
 */
class AuthAuditLogger
{
    /**
     * Log successful login
     *
     * @param User $user
     * @param array $context
     * @return void
     */
    public static function logSuccessfulLogin(User $user, array $context = []): void
    {
        self::log('auth.login.success', $user, array_merge([
            'action' => 'login',
            'status' => 'success',
        ], $context));
    }

    /**
     * Log failed login attempt
     *
     * @param string $email
     * @param string $reason
     * @param array $context
     * @return void
     */
    public static function logFailedLogin(string $email, string $reason, array $context = []): void
    {
        self::logWithoutUser('auth.login.failed', array_merge([
            'action' => 'login',
            'status' => 'failed',
            'email' => $email,
            'reason' => $reason,
        ], $context));
    }

    /**
     * Log successful registration
     *
     * @param User $user
     * @param array $context
     * @return void
     */
    public static function logSuccessfulRegistration(User $user, array $context = []): void
    {
        self::log('auth.register.success', $user, array_merge([
            'action' => 'register',
            'status' => 'success',
        ], $context));
    }

    /**
     * Log logout
     *
     * @param User $user
     * @param bool $allDevices
     * @param array $context
     * @return void
     */
    public static function logLogout(User $user, bool $allDevices = false, array $context = []): void
    {
        self::log('auth.logout', $user, array_merge([
            'action' => 'logout',
            'all_devices' => $allDevices,
        ], $context));
    }

    /**
     * Log password reset request
     *
     * @param string $email
     * @param array $context
     * @return void
     */
    public static function logPasswordResetRequest(string $email, array $context = []): void
    {
        self::logWithoutUser('auth.password.reset_requested', array_merge([
            'action' => 'password_reset_request',
            'email' => $email,
        ], $context));
    }

    /**
     * Log password reset completion
     *
     * @param User $user
     * @param array $context
     * @return void
     */
    public static function logPasswordReset(User $user, array $context = []): void
    {
        self::log('auth.password.reset', $user, array_merge([
            'action' => 'password_reset',
            'status' => 'success',
        ], $context));
    }

    /**
     * Log email verification
     *
     * @param User $user
     * @param array $context
     * @return void
     */
    public static function logEmailVerification(User $user, array $context = []): void
    {
        self::log('auth.email.verified', $user, array_merge([
            'action' => 'email_verification',
            'status' => 'success',
        ], $context));
    }

    /**
     * Log token refresh
     *
     * @param User $user
     * @param array $context
     * @return void
     */
    public static function logTokenRefresh(User $user, array $context = []): void
    {
        self::log('auth.token.refresh', $user, array_merge([
            'action' => 'token_refresh',
        ], $context));
    }

    /**
     * Log authentication attempt with rate limiting
     *
     * @param string $email
     * @param string $ip
     * @param array $context
     * @return void
     */
    public static function logRateLimitExceeded(string $email, string $ip, array $context = []): void
    {
        self::logWithoutUser('auth.rate_limit.exceeded', array_merge([
            'action' => 'rate_limit_exceeded',
            'email' => $email,
            'ip' => $ip,
        ], $context));
    }

    /**
     * Log with user context
     *
     * @param string $message
     * @param User $user
     * @param array $context
     * @return void
     */
    private static function log(string $message, User $user, array $context = []): void
    {
        Log::channel('auth')->info($message, array_merge(
            self::getBaseContext(),
            [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->name,
            ],
            $context
        ));
    }

    /**
     * Log without user context
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    private static function logWithoutUser(string $message, array $context = []): void
    {
        Log::channel('auth')->info($message, array_merge(
            self::getBaseContext(),
            $context
        ));
    }

    /**
     * Get base context for all logs
     *
     * @return array
     */
    private static function getBaseContext(): array
    {
        $request = request();

        return [
            'timestamp' => now()->toIso8601String(),
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'session_id' => session()->getId(),
        ];
    }
}
