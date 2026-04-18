<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Audit\Domain\Entities\AuditLog;

interface AuditServiceInterface
{
    /**
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

    public function pruneOlderThan(\DateTimeInterface $before): int;
}
