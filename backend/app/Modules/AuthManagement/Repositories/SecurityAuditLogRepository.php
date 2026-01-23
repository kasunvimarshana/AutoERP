<?php

namespace App\Modules\AuthManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\AuthManagement\Models\SecurityAuditLog;
use Illuminate\Database\Eloquent\Collection;

class SecurityAuditLogRepository extends BaseRepository
{
    public function __construct(SecurityAuditLog $model)
    {
        parent::__construct($model);
    }

    /**
     * Get logs for a specific user
     */
    public function getUserLogs(int $userId, int $limit = 50): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get logs for a specific tenant
     */
    public function getTenantLogs(int $tenantId, int $limit = 100): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get logs by event type
     */
    public function getByEventType(string $eventType, int $limit = 100): Collection
    {
        return $this->model
            ->where('event_type', $eventType)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get critical security events
     */
    public function getCriticalEvents(int $limit = 50): Collection
    {
        return $this->model
            ->where('severity', SecurityAuditLog::SEVERITY_CRITICAL)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed login attempts for user
     */
    public function getFailedLoginAttempts(int $userId, int $withinMinutes = 30): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('event_type', SecurityAuditLog::EVENT_LOGIN_FAILED)
            ->where('occurred_at', '>=', now()->subMinutes($withinMinutes))
            ->count();
    }

    /**
     * Get suspicious activities
     */
    public function getSuspiciousActivities(?int $tenantId = null, int $limit = 50): Collection
    {
        $query = $this->model
            ->where(function ($q) {
                $q->where('event_type', SecurityAuditLog::EVENT_SUSPICIOUS_ACTIVITY)
                  ->orWhere('severity', SecurityAuditLog::SEVERITY_CRITICAL);
            })
            ->orderBy('occurred_at', 'desc')
            ->limit($limit);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    /**
     * Cleanup old logs
     */
    public function cleanupOld(int $daysOld = 90): int
    {
        return $this->model
            ->where('occurred_at', '<', now()->subDays($daysOld))
            ->where('severity', '!=', SecurityAuditLog::SEVERITY_CRITICAL)
            ->delete();
    }
}
