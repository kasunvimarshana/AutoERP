<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;

interface AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog;

    public function findByUserId(int $userId, int $limit = 50): Collection;

    public function findByTenantId(string $tenantId, int $limit = 50): Collection;

    public function findByTraceId(string $traceId): Collection;
}
