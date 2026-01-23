<?php

namespace App\Modules\AuthManagement\Services;

use App\Models\User;
use App\Modules\AuthManagement\Repositories\PasswordResetTokenRepository;
use App\Modules\AuthManagement\Models\PasswordResetToken;
use App\Modules\AuthManagement\Events\PasswordResetRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PasswordResetService
{
    public function __construct(
        protected PasswordResetTokenRepository $tokenRepository
    ) {}

    /**
     * Request a password reset
     */
    public function requestReset(string $email): bool
    {
        try {
            DB::beginTransaction();

            $user = User::where('email', $email)->first();

            if (!$user) {
                // Don't reveal if user exists for security
                DB::commit();
                return true;
            }

            // Create reset token
            $tokenRecord = $this->tokenRepository->createToken($email, 60);

            DB::commit();

            // Dispatch event for email notification
            event(new PasswordResetRequested($email, $tokenRecord->token));

            Log::info('Password reset requested', ['email' => $email]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Password reset request failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        try {
            DB::beginTransaction();

            // Validate token
            $tokenRecord = $this->tokenRepository->findValidToken($email, $token);

            if (!$tokenRecord) {
                DB::rollBack();
                return false;
            }

            if ($tokenRecord->isExpired() || $tokenRecord->isUsed()) {
                DB::rollBack();
                return false;
            }

            // Find user
            $user = User::where('email', $email)->first();

            if (!$user) {
                DB::rollBack();
                return false;
            }

            // Update password
            $user->update([
                'password' => Hash::make($newPassword),
                'password_changed_at' => now(),
            ]);

            // Mark token as used
            $tokenRecord->markAsUsed();

            // Revoke all tokens to force re-login
            $user->tokens()->delete();

            DB::commit();

            Log::info('Password reset completed', [
                'user_id' => $user->id,
                'email' => $email
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Password reset failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify reset token validity
     */
    public function verifyToken(string $email, string $token): bool
    {
        $tokenRecord = $this->tokenRepository->findValidToken($email, $token);

        return $tokenRecord && !$tokenRecord->isExpired() && !$tokenRecord->isUsed();
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        try {
            $deleted = $this->tokenRepository->deleteExpired();
            Log::info('Cleaned up expired password reset tokens', ['count' => $deleted]);
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired tokens', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
