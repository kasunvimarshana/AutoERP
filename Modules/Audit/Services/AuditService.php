<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Audit\Models\AuditLog;
use Modules\Tenant\Services\TenantContext;

/**
 * AuditService
 *
 * Service for creating audit log entries
 */
class AuditService
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Log an audit event
     */
    public function log(array $data): AuditLog
    {
        $request = request();

        return AuditLog::create(array_merge($data, [
            'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            'user_id' => Auth::id(),
            'organization_id' => $this->tenantContext->getCurrentOrganizationId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]));
    }

    /**
     * Log a custom event
     */
    public function logEvent(
        string $event,
        ?string $auditableType = null,
        ?string $auditableId = null,
        array $metadata = []
    ): AuditLog {
        return $this->log([
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => [],
            'new_values' => [],
            'metadata' => $metadata,
        ]);
    }
}
