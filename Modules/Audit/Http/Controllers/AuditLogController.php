<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Audit\Http\Requests\IndexAuditLogRequest;
use Modules\Audit\Http\Resources\AuditLogResource;
use Modules\Audit\Models\AuditLog;
use Modules\Audit\Repositories\AuditLogRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Audit Log Controller
 *
 * Read-only API for accessing audit logs with comprehensive filtering,
 * statistics, and export capabilities
 */
class AuditLogController extends Controller
{
    public function __construct(
        private AuditLogRepository $auditLogRepository
    ) {}

    /**
     * List audit logs with filtering and pagination
     */
    public function index(IndexAuditLogRequest $request): AnonymousResourceCollection
    {
        $query = AuditLog::query()->with(['user']);

        // Apply filters
        $this->applyFilters($query, $request->getFilters());

        // Apply sorting
        $sorting = $request->getSorting();
        $query->orderBy($sorting['sort_by'], $sorting['sort_order']);

        // Paginate
        $pagination = $request->getPagination();
        $auditLogs = $query->paginate($pagination['per_page']);

        return AuditLogResource::collection($auditLogs);
    }

    /**
     * Get specific audit log details
     */
    public function show(string $id): AuditLogResource
    {
        $auditLog = AuditLog::with(['user', 'auditable'])->findOrFail($id);

        Gate::authorize('view', $auditLog);

        return new AuditLogResource($auditLog);
    }

    /**
     * Get audit statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        Gate::authorize('viewStatistics', AuditLog::class);

        $query = AuditLog::query();

        // Apply date range filter if provided
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }
        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        // Get statistics using repository methods
        $statistics = [
            'total_logs' => $query->count(),
            'by_event' => $this->auditLogRepository->getCountsByEvent($query),
            'by_auditable_type' => $this->auditLogRepository->getCountsByAuditableType($query),
            'by_user' => $this->auditLogRepository->getCountsByUser($query),
            'timeline' => $this->auditLogRepository->getTimeline($query, $request->input('group_by', 'day')),
            'recent_activity' => $this->getRecentActivity(),
        ];

        return response()->json([
            'data' => $statistics,
        ]);
    }

    /**
     * Export audit logs
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('export', AuditLog::class);

        $request->validate([
            'format' => ['required', 'string', 'in:csv,json'],
            'columns' => ['sometimes', 'array'],
            'columns.*' => ['string', 'in:id,event,auditable_type,auditable_id,user_id,organization_id,ip_address,created_at'],
            'from_date' => ['sometimes', 'date'],
            'to_date' => ['sometimes', 'date', 'after_or_equal:from_date'],
            'event' => ['sometimes', 'string'],
            'auditable_type' => ['sometimes', 'string'],
        ]);

        $format = $request->input('format', 'csv');
        $columns = $request->input('columns', [
            'id', 'event', 'auditable_type', 'auditable_id',
            'user_id', 'ip_address', 'created_at',
        ]);

        $query = AuditLog::query()->with(['user']);

        // Apply filters
        $filters = $request->only(['event', 'auditable_type', 'from_date', 'to_date']);
        $this->applyFilters($query, $filters);

        $query->orderBy('created_at', 'desc');

        $filename = 'audit_logs_'.now()->format('Y-m-d_His').'.'.$format;

        return response()->streamDownload(function () use ($query, $columns, $format) {
            if ($format === 'csv') {
                $this->exportCsv($query, $columns);
            } else {
                $this->exportJson($query, $columns);
            }
        }, $filename, [
            'Content-Type' => $format === 'csv' ? 'text/csv' : 'application/json',
        ]);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters): void
    {
        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        if (! empty($filters['auditable_id'])) {
            $query->where('auditable_id', $filters['auditable_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (! empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        // Search in JSON fields
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereRaw("JSON_SEARCH(old_values, 'one', ?) IS NOT NULL", ["%{$search}%"])
                    ->orWhereRaw("JSON_SEARCH(new_values, 'one', ?) IS NOT NULL", ["%{$search}%"])
                    ->orWhereRaw("JSON_SEARCH(metadata, 'one', ?) IS NOT NULL", ["%{$search}%"]);
            });
        }
    }

    /**
     * Get recent activity
     */
    protected function getRecentActivity(): array
    {
        return AuditLog::with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'auditable_type' => $log->auditable_type,
                    'user_name' => $log->user?->name ?? 'System',
                    'created_at' => $log->created_at->toIso8601String(),
                    'created_at_human' => $log->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Export audit logs as CSV
     */
    protected function exportCsv($query, array $columns): void
    {
        $handle = fopen('php://output', 'w');

        // Write headers
        fputcsv($handle, array_map('ucfirst', $columns));

        // Write data in chunks to handle large datasets
        $query->chunk(1000, function ($logs) use ($handle, $columns) {
            foreach ($logs as $log) {
                $row = [];
                foreach ($columns as $column) {
                    $value = match ($column) {
                        'user_id' => $log->user?->name ?? $log->user_id ?? 'System',
                        'created_at' => $log->created_at?->toDateTimeString(),
                        'old_values' => json_encode($log->old_values),
                        'new_values' => json_encode($log->new_values),
                        'metadata' => json_encode($log->metadata),
                        default => $log->$column ?? '',
                    };
                    $row[] = $value;
                }
                fputcsv($handle, $row);
            }
        });

        fclose($handle);
    }

    /**
     * Export audit logs as JSON
     */
    protected function exportJson($query, array $columns): void
    {
        echo '[';
        $first = true;

        $query->chunk(1000, function ($logs) use ($columns, &$first) {
            foreach ($logs as $log) {
                if (! $first) {
                    echo ',';
                }
                $first = false;

                $data = [];
                foreach ($columns as $column) {
                    $data[$column] = match ($column) {
                        'user_id' => [
                            'id' => $log->user_id,
                            'name' => $log->user?->name ?? 'System',
                        ],
                        'created_at' => $log->created_at?->toIso8601String(),
                        default => $log->$column,
                    };
                }
                echo json_encode($data);
            }
        });

        echo ']';
    }
}
