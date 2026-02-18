<?php

namespace App\Services\Audit;

use App\Models\Audit\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Service for managing and querying audit logs
 */
class AuditService
{
    /**
     * Get paginated audit logs with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('user');

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['tenant_id'])) {
            $query->byTenant($filters['tenant_id']);
        }

        if (isset($filters['event'])) {
            $query->event($filters['event']);
        }

        if (isset($filters['model'])) {
            $query->forModel($filters['model']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['tag'])) {
            $query->withTag($filters['tag']);
        }

        if (isset($filters['auditable_id'])) {
            $query->where('auditable_id', $filters['auditable_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get audit logs for a specific model instance
     *
     * @param string $modelClass
     * @param int $modelId
     * @return Collection
     */
    public function getModelAuditLogs(string $modelClass, int $modelId): Collection
    {
        return AuditLog::where('auditable_type', $modelClass)
            ->where('auditable_id', $modelId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user activity logs
     *
     * @param int $userId
     * @param int $days
     * @return Collection
     */
    public function getUserActivity(int $userId, int $days = 30): Collection
    {
        $startDate = Carbon::now()->subDays($days);
        
        return AuditLog::byUser($userId)
            ->where('created_at', '>=', $startDate)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get tenant activity logs
     *
     * @param string $tenantId
     * @param int $days
     * @return Collection
     */
    public function getTenantActivity(string $tenantId, int $days = 30): Collection
    {
        $startDate = Carbon::now()->subDays($days);
        
        return AuditLog::byTenant($tenantId)
            ->where('created_at', '>=', $startDate)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activity statistics
     *
     * @param array $filters
     * @return array
     */
    public function getActivityStats(array $filters = []): array
    {
        $query = AuditLog::query();

        // Apply filters
        if (isset($filters['tenant_id'])) {
            $query->byTenant($filters['tenant_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        } else {
            // Default to last 30 days
            $query->where('created_at', '>=', Carbon::now()->subDays(30));
        }

        return [
            'total_events' => $query->count(),
            'events_by_type' => $query->selectRaw('event, COUNT(*) as count')
                ->groupBy('event')
                ->pluck('count', 'event')
                ->toArray(),
            'events_by_model' => $query->selectRaw('auditable_type, COUNT(*) as count')
                ->groupBy('auditable_type')
                ->pluck('count', 'auditable_type')
                ->toArray(),
            'top_users' => $query->selectRaw('user_id, COUNT(*) as count')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'user_id')
                ->toArray(),
        ];
    }

    /**
     * Export audit logs to array
     *
     * @param array $filters
     * @return array
     */
    public function exportAuditLogs(array $filters = []): array
    {
        $logs = $this->getAuditLogs($filters, PHP_INT_MAX);
        
        return $logs->items()->map(function ($log) {
            return [
                'Date/Time' => $log->created_at->toDateTimeString(),
                'User' => $log->user?->name ?? 'System',
                'Event' => $log->event,
                'Model' => class_basename($log->auditable_type),
                'ID' => $log->auditable_id,
                'Description' => $log->description,
                'IP Address' => $log->ip_address,
            ];
        })->toArray();
    }

    /**
     * Manually log an audit event
     *
     * @param string $event
     * @param string $auditableType
     * @param int $auditableId
     * @param array $tags
     * @param array $metadata
     * @return AuditLog
     */
    public function logEvent(
        string $event,
        string $auditableType,
        int $auditableId,
        array $tags = [],
        array $metadata = []
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'audit_id' => \Illuminate\Support\Str::uuid(),
            'user_id' => auth()->id(),
            'tenant_id' => $request->attributes->get('tenant_id'),
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tags' => $tags,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Clean up old audit logs
     *
     * @param int $daysToKeep
     * @return int Number of deleted records
     */
    public function cleanupOldLogs(int $daysToKeep = 365): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        // Note: This bypasses the delete prevention in the model
        return AuditLog::where('created_at', '<', $cutoffDate)->forceDelete();
    }
}
