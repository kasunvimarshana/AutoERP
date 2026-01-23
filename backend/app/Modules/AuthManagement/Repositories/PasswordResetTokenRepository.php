<?php

namespace App\Modules\AuthManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\AuthManagement\Models\PasswordResetToken;
use Illuminate\Support\Carbon;

class PasswordResetTokenRepository extends BaseRepository
{
    public function __construct(PasswordResetToken $model)
    {
        parent::__construct($model);
    }

    /**
     * Create a new password reset token
     */
    public function createToken(string $email, int $expiresInMinutes = 60): PasswordResetToken
    {
        // Delete old tokens for this email
        $this->deleteByEmail($email);

        return $this->create([
            'email' => $email,
            'token' => PasswordResetToken::generateToken(),
            'created_at' => now(),
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Find a valid token
     */
    public function findValidToken(string $email, string $token): ?PasswordResetToken
    {
        return $this->model
            ->where('email', $email)
            ->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Delete tokens by email
     */
    public function deleteByEmail(string $email): int
    {
        return $this->model->where('email', $email)->delete();
    }

    /**
     * Delete expired tokens
     */
    public function deleteExpired(): int
    {
        return $this->model->where('expires_at', '<', now())->delete();
    }
}
