<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Core\Domain\Entities\AuditLog;

/**
 * Contract for the application-level audit service.
 *
 * The service is responsible for recording, retrieving, and pruning audit logs
 * in a way that is completely decoupled from any specific module or Eloquent model.
 */
interface AuditServiceInterface
{
    /**
     * Record an audit log entry from raw data.
     *
     * @param  array{
     *     event: string,
     *     auditable_type: string,
     *     auditable_id: int|string,
     *     tenant_id?: int|null,
     *     user_id?: int|null,
     *     old_values?: array|null,
     *     new_values?: array|null,
     *     url?: string|null,
     *     ip_address?: string|null,
     *     user_agent?: string|null,
     *     tags?: array|null,
     *     metadata?: array|null,
     * }  $data
     */
    public function record(array $data): AuditLog;

    /**
     * Retrieve a single audit log entry by ID.
     */
    public function find(int $id): ?AuditLog;

    /**
     * Return all audit log entries for a specific auditable resource.
     *
     * @param  string  $auditableType  Morph type / model class name.
     * @param  int|string  $auditableId  Primary key of the resource.
     */
    public function forAuditable(string $auditableType, int|string $auditableId): Collection;

    /**
     * Paginate audit log entries for a specific auditable resource.
     */
    public function forAuditablePaginated(
        string $auditableType,
        int|string $auditableId,
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator;

    /**
     * Paginate all audit entries for a tenant.
     */
    public function forTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Paginate all audit entries performed by a user.
     */
    public function forUser(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Prune audit entries older than the given date.  Returns number deleted.
     */
    public function pruneOlderThan(\DateTimeInterface $before): int;
}
