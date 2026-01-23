<?php

namespace App\Modules\AuthManagement\Services;

use App\Models\User;
use App\Modules\AuthManagement\Repositories\UserSessionRepository;
use App\Modules\AuthManagement\Models\UserSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SessionService
{
    public function __construct(
        protected UserSessionRepository $sessionRepository
    ) {}

    /**
     * Create a new session for user
     */
    public function createSession(User $user, int $expiresInMinutes = 1440): UserSession
    {
        try {
            $sessionId = Str::random(64);

            $session = $this->sessionRepository->createSession(
                $user->id,
                $sessionId,
                $expiresInMinutes
            );

            Log::info('Session created', [
                'user_id' => $user->id,
                'session_id' => $sessionId
            ]);

            return $session;
        } catch (\Exception $e) {
            Log::error('Session creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get active sessions for user
     */
    public function getUserSessions(User $user): array
    {
        $sessions = $this->sessionRepository->getUserActiveSessions($user->id);

        return $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'device_type' => $session->device_type,
                'device_name' => $session->device_name,
                'ip_address' => $session->ip_address,
                'last_activity' => $session->last_activity,
                'is_current' => false, // TODO: implement current session detection
            ];
        })->toArray();
    }

    /**
     * Terminate a specific session
     */
    public function terminateSession(int $sessionId): bool
    {
        try {
            $session = $this->sessionRepository->findById($sessionId);

            if (!$session) {
                return false;
            }

            $session->terminate();

            Log::info('Session terminated', [
                'session_id' => $sessionId,
                'user_id' => $session->user_id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Session termination failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Terminate all sessions for user except current
     */
    public function terminateOtherSessions(User $user, ?string $currentSessionId = null): int
    {
        try {
            $count = $this->sessionRepository->terminateOtherSessions(
                $user->id,
                $currentSessionId
            );

            Log::info('Other sessions terminated', [
                'user_id' => $user->id,
                'count' => $count
            ]);

            return $count;
        } catch (\Exception $e) {
            Log::error('Terminating other sessions failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Update session activity
     */
    public function touchSession(string $sessionId): bool
    {
        try {
            $session = $this->sessionRepository->findActiveSession($sessionId);

            if (!$session) {
                return false;
            }

            $session->touch();

            return true;
        } catch (\Exception $e) {
            Log::error('Session touch failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        try {
            $count = $this->sessionRepository->cleanupExpired();
            Log::info('Cleaned up expired sessions', ['count' => $count]);
            return $count;
        } catch (\Exception $e) {
            Log::error('Session cleanup failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Delete old inactive sessions
     */
    public function deleteOldSessions(int $daysOld = 30): int
    {
        try {
            $count = $this->sessionRepository->deleteOldInactive($daysOld);
            Log::info('Deleted old inactive sessions', ['count' => $count]);
            return $count;
        } catch (\Exception $e) {
            Log::error('Old session deletion failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
