<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Audit\Domain\Entities\AuditLog;

interface AuditRepositoryInterface
{
    public function record(AuditLog $log): AuditLog;

    public function find(int $id): ?AuditLog;

    public function forAuditable(string $auditableType, int|string $auditableId): Collection;

    public function forAuditablePaginated(
        string $auditableType,
        int|string $auditableId,
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator;

    public function forTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    public function forUser(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    public function forEvent(string $event, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    public function pruneOlderThan(\DateTimeInterface $before): int;
}
