<?php

namespace App\Modules\AuthManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\AuthManagement\Models\UserSession;

class UserSessionRepository extends BaseRepository
{
    public function __construct(UserSession $model)
    {
        parent::__construct($model);
    }

    /**
     * Create a new session
     */
    public function createSession(int $userId, string $sessionId, int $expiresInMinutes = 1440): UserSession
    {
        $userAgent = request()->userAgent() ?? 'Unknown';
        $deviceInfo = UserSession::parseDeviceInfo($userAgent);

        return $this->create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'device_name' => $deviceInfo['device_name'],
            'last_activity' => now(),
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'is_active' => true,
        ]);
    }

    /**
     * Find active session by session ID
     */
    public function findActiveSession(string $sessionId): ?UserSession
    {
        return $this->model
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Get active sessions for user
     */
    public function getUserActiveSessions(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    /**
     * Terminate all sessions for user except current
     */
    public function terminateOtherSessions(int $userId, ?string $currentSessionId = null): int
    {
        $query = $this->model
            ->where('user_id', $userId)
            ->where('is_active', true);

        if ($currentSessionId) {
            $query->where('session_id', '!=', $currentSessionId);
        }

        return $query->update(['is_active' => false]);
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpired(): int
    {
        return $this->model
            ->where('expires_at', '<', now())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Delete old inactive sessions
     */
    public function deleteOldInactive(int $daysOld = 30): int
    {
        return $this->model
            ->where('is_active', false)
            ->where('updated_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
