<?php

declare(strict_types=1);

namespace Modules\Core\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Core\Domain\Entities\AuditLog;

/**
 * Repository contract for persisting and querying audit log entries.
 *
 * Implementations must be stateless between calls (no query builder carry-over).
 */
interface AuditRepositoryInterface
{
    /**
     * Persist a new audit log entry and return it with its assigned ID.
     */
    public function record(AuditLog $log): AuditLog;

    /**
     * Retrieve a single audit log entry by its primary key.
     */
    public function find(int $id): ?AuditLog;

    /**
     * Return all audit entries for a specific auditable resource.
     *
     * @param  string  $auditableType  Fully-qualified model class name or morph alias.
     * @param  int|string  $auditableId  Primary key of the auditable record.
     */
    public function forAuditable(string $auditableType, int|string $auditableId): Collection;

    /**
     * Return paginated audit entries for a specific auditable resource.
     *
     * @param  string  $auditableType  Fully-qualified model class name or morph alias.
     * @param  int|string  $auditableId  Primary key of the auditable record.
     */
    public function forAuditablePaginated(
        string $auditableType,
        int|string $auditableId,
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator;

    /**
     * Return paginated audit entries scoped to a tenant.
     */
    public function forTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Return paginated audit entries performed by a user.
     */
    public function forUser(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Return paginated audit entries filtered by event type.
     */
    public function forEvent(string $event, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Prune (hard-delete) audit entries older than the given date.
     *
     * Returns the number of rows deleted.
     */
    public function pruneOlderThan(\DateTimeInterface $before): int;
}
