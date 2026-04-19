<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\RepositoryInterfaces;

use Modules\Audit\Domain\Entities\AuditLog;

interface AuditRepositoryInterface
{
    public function record(AuditLog $log): AuditLog;

    public function find(int $id): ?AuditLog;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(
        array $filters,
        int $perPage = 15,
        int $page = 1,
        ?string $sortField = 'occurred_at',
        string $sortDirection = 'desc'
    ): mixed;

    /**
     * @return iterable<int, AuditLog>
     */
    public function forAuditable(string $auditableType, int|string $auditableId): iterable;

    public function forAuditablePaginated(
        string $auditableType,
        int|string $auditableId,
        int $perPage = 15,
        int $page = 1
    ): mixed;

    public function forTenant(int $tenantId, int $perPage = 15, int $page = 1): mixed;

    public function forUser(int $userId, int $perPage = 15, int $page = 1): mixed;

    public function forEvent(string $event, int $perPage = 15, int $page = 1): mixed;

    public function pruneOlderThan(\DateTimeInterface $before): int;
}
