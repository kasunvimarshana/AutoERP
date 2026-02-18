<?php

declare(strict_types=1);

namespace Modules\IAM\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Events\UserLoggedIn;
use Modules\IAM\Events\UserLoggedOut;
use Modules\IAM\Events\UserRegistered;
use Modules\IAM\Exceptions\AuthenticationException;

/**
 * Authentication Service
 * 
 * Handles user authentication, registration, and session management.
 * Implements JWT-based stateless authentication with multi-tenancy support.
 */
class AuthService
{
    public function __construct(
        private JwtService $jwtService
    ) {}
    
    /**
     * Authenticate user and generate JWT tokens
     */
    public function login(array $credentials, ?int $tenantId = null): array
    {
        $email = $credentials['email'];
        $password = $credentials['password'];
        
        // Rate limiting
        $rateLimitKey = 'login:' . $email;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }
        
        // Find user
        $user = User::where('email', $email)->first();
        
        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($rateLimitKey, 300); // 5 minutes
            
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        // Check if user is active
        if (!$user->is_active) {
            throw new AuthenticationException('Your account has been deactivated. Please contact support.');
        }
        
        // Validate tenant access if specified
        if ($tenantId && $user->tenant_id !== $tenantId) {
            throw new AuthenticationException('You do not have access to this tenant.');
        }
        
        // Clear rate limit on successful login
        RateLimiter::clear($rateLimitKey);
        
        // Generate JWT tokens
        $tokens = $this->jwtService->generateToken($user, $tenantId);
        
        // Log authentication event
        Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'tenant_id' => $tokens['tenant_id'],
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        // Dispatch event
        event(new UserLoggedIn($user, $tokens['tenant_id']));
        
        // Update last login timestamp
        $user->update(['last_login_at' => now()]);
        
        return [
            'user' => $user,
            'tokens' => $tokens,
        ];
    }
    
    /**
     * Register new user
     */
    public function register(array $data): array
    {
        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'tenant_id' => $data['tenant_id'] ?? null,
                'is_active' => true,
                'email_verified_at' => $data['auto_verify'] ?? false ? now() : null,
            ]);
            
            // Assign default role if specified
            if (isset($data['role_id'])) {
                $user->roles()->attach($data['role_id']);
            }
            
            // Generate tokens
            $tokens = $this->jwtService->generateToken($user, $data['tenant_id'] ?? null);
            
            // Log registration
            Log::info('User registered', [
                'user_id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id,
            ]);
            
            // Dispatch event
            event(new UserRegistered($user));
            
            DB::commit();
            
            return [
                'user' => $user,
                'tokens' => $tokens,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Logout user and revoke tokens
     */
    public function logout(User $user, string $token): bool
    {
        // Revoke current token
        $this->jwtService->revokeToken($token);
        
        // Log logout
        Log::info('User logged out', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
        
        // Dispatch event
        event(new UserLoggedOut($user));
        
        return true;
    }
    
    /**
     * Logout from all devices
     */
    public function logoutAllDevices(User $user): bool
    {
        // Revoke all user tokens
        $this->jwtService->revokeAllUserTokens($user->id);
        
        Log::info('User logged out from all devices', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
        
        return true;
    }
    
    /**
     * Refresh authentication tokens
     */
    public function refresh(string $refreshToken): ?array
    {
        $tokens = $this->jwtService->refreshToken($refreshToken);
        
        if (!$tokens) {
            return null;
        }
        
        Log::info('Token refreshed', [
            'tenant_id' => $tokens['tenant_id'],
        ]);
        
        return $tokens;
    }
    
    /**
     * Validate JWT token and return user
     */
    public function validateToken(string $token): ?User
    {
        $payload = $this->jwtService->decodeToken($token);
        
        if (!$payload) {
            return null;
        }
        
        $user = User::find($payload['sub']);
        
        if (!$user || !$user->is_active) {
            return null;
        }
        
        return $user;
    }
    
    /**
     * Get tenant ID from token
     */
    public function getTenantFromToken(string $token): ?int
    {
        $payload = $this->jwtService->decodeToken($token);
        
        return $payload['tenant_id'] ?? null;
    }
    
    /**
     * Initiate password reset
     */
    public function initiatePasswordReset(string $email): bool
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            // Don't reveal if email exists
            return true;
        }
        
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        
        // Store reset token (expires in 1 hour)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($resetToken),
                'created_at' => now(),
            ]
        );
        
        // TODO: Implement email sending in production
        // For now, log the reset token (REMOVE IN PRODUCTION)
        if (config('app.debug')) {
            Log::info('Password reset token (DEBUG ONLY)', [
                'email' => $email,
                'token' => $resetToken,
            ]);
        }
        
        // In production, uncomment this and remove debug logging:
        // Mail::to($user->email)->send(new PasswordResetMail($resetToken));
        
        Log::info('Password reset initiated', ['email' => $email]);
        
        return true;
    }
    
    /**
     * Reset password using token
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();
        
        if (!$resetRecord) {
            throw new AuthenticationException('Invalid or expired reset token.');
        }
        
        // Check if token is expired (1 hour)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw new AuthenticationException('Reset token has expired.');
        }
        
        // Verify token
        if (!Hash::check($token, $resetRecord->token)) {
            throw new AuthenticationException('Invalid reset token.');
        }
        
        // Update password
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new AuthenticationException('User not found.');
        }
        
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
        
        // Delete reset token
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        
        // Revoke all existing tokens
        $this->jwtService->revokeAllUserTokens($user->id);
        
        Log::info('Password reset successful', ['user_id' => $user->id]);
        
        return true;
    }
    
    /**
     * Verify user email
     */
    public function verifyEmail(int $userId, string $verificationToken): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            throw new AuthenticationException('User not found.');
        }
        
        if ($user->email_verified_at) {
            return true; // Already verified
        }
        
        // Verify token from database
        $storedToken = DB::table('email_verification_tokens')
            ->where('user_id', $userId)
            ->first();
        
        if (!$storedToken) {
            throw new AuthenticationException('Invalid verification token.');
        }
        
        // Check if token is expired (24 hours)
        if (now()->diffInHours($storedToken->created_at) > 24) {
            DB::table('email_verification_tokens')->where('user_id', $userId)->delete();
            throw new AuthenticationException('Verification token has expired.');
        }
        
        // Verify token matches
        if (!Hash::check($verificationToken, $storedToken->token)) {
            throw new AuthenticationException('Invalid verification token.');
        }
        
        // Mark email as verified
        $user->update(['email_verified_at' => now()]);
        
        // Delete verification token
        DB::table('email_verification_tokens')->where('user_id', $userId)->delete();
        
        Log::info('Email verified', ['user_id' => $user->id]);
        
        return true;
    }
}
