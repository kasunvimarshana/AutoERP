<?php
declare(strict_types=1);
namespace Modules\Audit\Domain\RepositoryInterfaces;

use Modules\Audit\Domain\Entities\AuditLog;

interface AuditLogRepositoryInterface
{
    public function findById(int $id): ?AuditLog;

    /** Retrieve paginated audit logs for a tenant with optional filters. */
    public function findByTenant(
        int $tenantId,
        array $filters = [],   // entityType, entityId, userId, event, dateFrom, dateTo
        int $perPage = 50,
        int $page = 1
    ): array; // ['data' => AuditLog[], 'total' => int]

    public function findByEntity(int $tenantId, string $entityType, string $entityId): array;

    public function create(array $data): AuditLog;

    /** Hard-delete logs older than the given date (for retention policy). */
    public function deleteOlderThan(\DateTimeInterface $before): int;
}
