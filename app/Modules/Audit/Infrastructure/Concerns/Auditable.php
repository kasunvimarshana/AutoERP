<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Concerns;

use Modules\Audit\Domain\Entities\AuditLog;

trait Auditable
{
    use ResolvesAuditContext;

    protected function recordAudit(
        string $event,
        string $auditableType,
        int|string $auditableId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $tenantId = null,
        ?int $userId = null,
        ?array $tags = null,
        ?array $metadata = null,
    ): ?AuditLog {
        $service = $this->resolveAuditService();
        if ($service === null) {
            return null;
        }

        try {
            return $service->record([
                'event' => $event,
                'auditable_type' => $auditableType,
                'auditable_id' => (string) $auditableId,
                'tenant_id' => $tenantId ?? $this->resolveAuditTenantId(),
                'user_id' => $userId ?? $this->resolveAuditUserId(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'url' => $this->resolveAuditUrl(),
                'ip_address' => $this->resolveAuditIpAddress(),
                'user_agent' => $this->resolveAuditUserAgent(),
                'tags' => $tags,
                'metadata' => $metadata,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }
}
