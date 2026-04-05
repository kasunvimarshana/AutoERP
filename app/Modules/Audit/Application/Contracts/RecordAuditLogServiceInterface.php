<?php
declare(strict_types=1);
namespace Modules\Audit\Application\Contracts;

use Modules\Audit\Domain\Entities\AuditLog;

interface RecordAuditLogServiceInterface
{
    public function record(
        int $tenantId,
        ?int $userId,
        string $event,
        string $entityType,
        ?string $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $url = null,
    ): AuditLog;
}
