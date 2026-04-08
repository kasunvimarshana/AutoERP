<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\RepositoryInterfaces;

use Modules\Audit\Domain\Entities\AuditLog;

interface AuditLogRepositoryInterface
{
    public function findById(string $id): ?AuditLog;

    /** @return AuditLog[] */
    public function findByAuditable(string $tenantId, string $type, string $id): array;

    /** @return AuditLog[] */
    public function findByTenant(string $tenantId, array $filters = []): array;

    public function save(AuditLog $log): void;
}
