<?php

namespace Modules\IAM\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\IAM\Models\LoginAttempt;

class LoginAttemptRepository extends BaseRepository
{
    protected function model(): string
    {
        return LoginAttempt::class;
    }

    public function recordAttempt(
        ?int $userId,
        string $email,
        string $ipAddress,
        string $userAgent,
        bool $successful,
        ?string $failedReason = null,
        ?int $tenantId = null
    ): LoginAttempt {
        return $this->create([
            'user_id' => $userId,
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'failed_reason' => $failedReason,
            'tenant_id' => $tenantId,
        ]);
    }

    public function getRecentFailedAttempts(string $email, int $minutes = 15): Collection
    {
        return $this->model
            ->where('email', $email)
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->get();
    }

    public function getRecentFailedAttemptsForIp(string $ipAddress, int $minutes = 15): Collection
    {
        return $this->model
            ->where('ip_address', $ipAddress)
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->get();
    }

    public function countRecentFailedAttempts(string $email, int $minutes = 15): int
    {
        return $this->model
            ->where('email', $email)
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    public function countRecentFailedAttemptsForIp(string $ipAddress, int $minutes = 15): int
    {
        return $this->model
            ->where('ip_address', $ipAddress)
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    public function clearOldAttempts(int $days = 30): int
    {
        return $this->model
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
