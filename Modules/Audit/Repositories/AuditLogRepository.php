<?php

declare(strict_types=1);

namespace Modules\Audit\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Audit\Models\AuditLog;
use Modules\Core\Repositories\BaseRepository;

/**
 * AuditLog Repository
 *
 * Handles data access operations for AuditLog model with
 * specialized filtering and querying methods
 */
class AuditLogRepository extends BaseRepository
{
    /**
     * Make a new AuditLog model instance.
     */
    protected function makeModel(): Model
    {
        return new AuditLog;
    }

    /**
     * Get audit logs for a specific model.
     */
    public function getForModel(string $modelType, string $modelId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('auditable_type', $modelType)
            ->where('auditable_id', $modelId)
            ->with(['user', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit logs for a specific user.
     */
    public function getForUser(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byUser($userId)
            ->with(['organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit logs for a specific organization.
     */
    public function getForOrganization(string $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byOrganization($organizationId)
            ->with(['user'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit logs by event type.
     */
    public function getByEvent(string $event, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byEvent($event)
            ->with(['user', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit logs by date range.
     */
    public function getByDateRange(?string $fromDate, ?string $toDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->dateRange($fromDate, $toDate)
            ->with(['user', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit logs with advanced filters.
     */
    public function getFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (! empty($filters['event'])) {
            $query->byEvent($filters['event']);
        }

        if (! empty($filters['auditable_type'])) {
            $query->byAuditableType($filters['auditable_type']);
        }

        if (! empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (! empty($filters['organization_id'])) {
            $query->byOrganization($filters['organization_id']);
        }

        if (! empty($filters['from_date']) || ! empty($filters['to_date'])) {
            $query->dateRange($filters['from_date'] ?? null, $filters['to_date'] ?? null);
        }

        if (! empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        $query->with(['user', 'organization'])->latest();

        return $query->paginate($perPage);
    }

    /**
     * Get recent audit logs.
     */
    public function getRecent(int $limit = 50): Collection
    {
        return $this->model
            ->with(['user', 'organization'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for tenant.
     */
    public function getForTenant(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->with(['user', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit log statistics by event type.
     */
    public function getEventStatistics(?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = $this->model->query();

        if ($fromDate || $toDate) {
            $query->dateRange($fromDate, $toDate);
        }

        return $query->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get audit log statistics by user.
     */
    public function getUserStatistics(?string $fromDate = null, ?string $toDate = null, int $limit = 10): Collection
    {
        $query = $this->model->query();

        if ($fromDate || $toDate) {
            $query->dateRange($fromDate, $toDate);
        }

        return $query->selectRaw('user_id, COUNT(*) as count')
            ->whereNotNull('user_id')
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit log statistics by model type.
     */
    public function getModelTypeStatistics(?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = $this->model->query();

        if ($fromDate || $toDate) {
            $query->dateRange($fromDate, $toDate);
        }

        return $query->selectRaw('auditable_type, COUNT(*) as count')
            ->groupBy('auditable_type')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get changes made to a specific field.
     */
    public function getFieldChanges(string $modelType, string $field, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byAuditableType($modelType)
            ->where(function ($query) use ($field) {
                $query->whereJsonContains('old_values', [$field])
                    ->orWhereJsonContains('new_values', [$field]);
            })
            ->with(['user', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit logs by IP address.
     */
    public function getByIpAddress(string $ipAddress, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('ip_address', $ipAddress)
            ->with(['user', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Search audit logs.
     */
    public function search(string $searchTerm, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where(function ($query) use ($searchTerm) {
                $query->where('event', 'like', "%{$searchTerm}%")
                    ->orWhere('auditable_type', 'like', "%{$searchTerm}%")
                    ->orWhere('auditable_id', 'like', "%{$searchTerm}%")
                    ->orWhere('ip_address', 'like', "%{$searchTerm}%");
            })
            ->with(['user', 'organization'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit trail for a model with full history.
     */
    public function getAuditTrail(string $modelType, string $modelId): Collection
    {
        return $this->model
            ->where('auditable_type', $modelType)
            ->where('auditable_id', $modelId)
            ->with(['user', 'organization'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Count logs by criteria.
     */
    public function countBy(array $criteria): int
    {
        $query = $this->model->query();

        if (isset($criteria['event'])) {
            $query->byEvent($criteria['event']);
        }

        if (isset($criteria['user_id'])) {
            $query->byUser($criteria['user_id']);
        }

        if (isset($criteria['organization_id'])) {
            $query->byOrganization($criteria['organization_id']);
        }

        if (isset($criteria['auditable_type'])) {
            $query->byAuditableType($criteria['auditable_type']);
        }

        if (isset($criteria['from_date']) || isset($criteria['to_date'])) {
            $query->dateRange($criteria['from_date'] ?? null, $criteria['to_date'] ?? null);
        }

        return $query->count();
    }

    /**
     * Get unique event types.
     */
    public function getUniqueEvents(): Collection
    {
        return $this->model
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');
    }

    /**
     * Get unique auditable types.
     */
    public function getUniqueAuditableTypes(): Collection
    {
        return $this->model
            ->select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type');
    }

    /**
     * Cleanup old audit logs.
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        return $this->model
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Export audit logs to array.
     */
    public function export(array $filters = []): Collection
    {
        $query = $this->model->query();

        if (! empty($filters['event'])) {
            $query->byEvent($filters['event']);
        }

        if (! empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (! empty($filters['organization_id'])) {
            $query->byOrganization($filters['organization_id']);
        }

        if (! empty($filters['from_date']) || ! empty($filters['to_date'])) {
            $query->dateRange($filters['from_date'] ?? null, $filters['to_date'] ?? null);
        }

        return $query->with(['user', 'organization'])->latest()->get();
    }

    /**
     * Get counts by event type
     */
    public function getCountsByEvent($query): array
    {
        return (clone $query)
            ->select('event', \DB::raw('COUNT(*) as count'))
            ->groupBy('event')
            ->orderByDesc('count')
            ->pluck('count', 'event')
            ->toArray();
    }

    /**
     * Get counts by auditable type
     */
    public function getCountsByAuditableType($query): array
    {
        return (clone $query)
            ->select('auditable_type', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('auditable_type')
            ->groupBy('auditable_type')
            ->orderByDesc('count')
            ->pluck('count', 'auditable_type')
            ->toArray();
    }

    /**
     * Get counts by user
     */
    public function getCountsByUser($query): array
    {
        $userCounts = (clone $query)
            ->select('user_id', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Load user details
        $userIds = $userCounts->pluck('user_id');
        $users = \Modules\Auth\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        return $userCounts->map(function ($item) use ($users) {
            $user = $users->get($item->user_id);

            return [
                'user_id' => $item->user_id,
                'user_name' => $user ? $user->name : 'Unknown',
                'user_email' => $user ? $user->email : null,
                'count' => $item->count,
            ];
        })->toArray();
    }

    /**
     * Get audit log timeline
     */
    public function getTimeline($query, string $groupBy = 'day'): array
    {
        $dateFormats = [
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
        ];

        $dateFormat = $dateFormats[$groupBy] ?? $dateFormats['day'];

        return (clone $query)
            ->select(\DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"), \DB::raw('COUNT(*) as count'))
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('count', 'period')
            ->toArray();
    }
}
