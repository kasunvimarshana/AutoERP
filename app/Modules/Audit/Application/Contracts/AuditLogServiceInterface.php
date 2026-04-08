<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Contracts;

use Modules\Audit\Domain\Entities\AuditLog;

interface AuditLogServiceInterface
{
    public function log(
        string $tenantId,
        string $event,
        string $auditableType,
        string $auditableId,
        array $oldValues = [],
        array $newValues = [],
        array $context = [],
    ): AuditLog;

    /** @return AuditLog[] */
    public function getForEntity(string $tenantId, string $type, string $id): array;

    /** @return AuditLog[] */
    public function getForTenant(string $tenantId, array $filters = []): array;

    public function getById(string $id): AuditLog;
}
