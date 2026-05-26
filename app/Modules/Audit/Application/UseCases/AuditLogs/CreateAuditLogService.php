<?php

declare(strict_types=1);

namespace Modules\Audit\Application\UseCases\AuditLogs;

use Modules\Audit\Application\Contracts\UseCases\AuditLogs\CreateAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\LogActivityServiceInterface;
use Modules\Audit\Application\DTOs\AuditLogActivityData;
use Modules\Core\Application\Results\Result;

final class CreateAuditLogService implements CreateAuditLogServiceInterface
{
    public function __construct(private readonly LogActivityServiceInterface $logActivity)
    {
    }

    public function execute(array $payload): Result
    {
        // Keep compatibility for existing internal callers while enforcing the same write path.
        return $this->logActivity->execute(new AuditLogActivityData(
            event: (string) ($payload['event'] ?? ''),
            auditableType: (string) ($payload['auditable_type'] ?? ''),
            auditableId: (string) ($payload['auditable_id'] ?? ''),
            tenantId: isset($payload['tenant_id']) && is_numeric($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            organizationUnitId: isset($payload['organization_unit_id']) && is_numeric($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : null,
            userId: isset($payload['user_id']) && is_numeric($payload['user_id']) ? (int) $payload['user_id'] : null,
            oldValues: isset($payload['old_values']) && is_array($payload['old_values']) ? $payload['old_values'] : null,
            newValues: isset($payload['new_values']) && is_array($payload['new_values']) ? $payload['new_values'] : null,
            metadata: isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : null,
            url: isset($payload['url']) ? (string) $payload['url'] : null,
            ipAddress: isset($payload['ip_address']) ? (string) $payload['ip_address'] : null,
            userAgent: isset($payload['user_agent']) ? (string) $payload['user_agent'] : null,
            tags: isset($payload['tags']) && is_array($payload['tags']) ? array_values($payload['tags']) : null,
            occurredAt: isset($payload['occurred_at']) ? (string) $payload['occurred_at'] : null,
        ));
    }
}