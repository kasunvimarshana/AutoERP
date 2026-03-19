<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Domain\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    public function findByUserId(int $userId, int $limit = 50): Collection
    {
        return AuditLog::where('user_id', $userId)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    public function findByTenantId(string $tenantId, int $limit = 50): Collection
    {
        return AuditLog::where('tenant_id', $tenantId)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    public function findByTraceId(string $traceId): Collection
    {
        return AuditLog::where('trace_id', $traceId)->get();
    }
}
