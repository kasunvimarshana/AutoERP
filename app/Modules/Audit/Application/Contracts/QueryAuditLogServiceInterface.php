<?php
declare(strict_types=1);
namespace Modules\Audit\Application\Contracts;

interface QueryAuditLogServiceInterface
{
    public function findById(int $id): \Modules\Audit\Domain\Entities\AuditLog;

    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 50, int $page = 1): array;

    public function findByEntity(int $tenantId, string $entityType, string $entityId): array;

    /** Purge audit logs older than the given number of days. Returns count deleted. */
    public function purgeOlderThan(int $days): int;
}
