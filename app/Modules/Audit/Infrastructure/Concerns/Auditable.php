<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Concerns;

use Illuminate\Support\Facades\Auth;
use Modules\Audit\Application\Contracts\AuditServiceInterface;
use Modules\Audit\Domain\Entities\AuditLog;

trait Auditable
{
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
                'event'          => $event,
                'auditable_type' => $auditableType,
                'auditable_id'   => (string) $auditableId,
                'tenant_id'      => $tenantId ?? $this->resolveAuditTenantId(),
                'user_id'        => $userId ?? $this->resolveAuditUserId(),
                'old_values'     => $oldValues,
                'new_values'     => $newValues,
                'url'            => $this->resolveAuditUrl(),
                'ip_address'     => $this->resolveAuditIpAddress(),
                'user_agent'     => $this->resolveAuditUserAgent(),
                'tags'           => $tags,
                'metadata'       => $metadata,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditService(): ?AuditServiceInterface
    {
        try {
            return app(AuditServiceInterface::class);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditUserId(): ?int
    {
        try {
            return Auth::id();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditTenantId(): ?int
    {
        try {
            return Auth::user()?->tenant_id ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditUrl(): ?string
    {
        try {
            return app()->runningInConsole() ? 'console' : request()->fullUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditIpAddress(): ?string
    {
        try {
            return app()->runningInConsole() ? null : request()->ip();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditUserAgent(): ?string
    {
        try {
            return app()->runningInConsole() ? null : request()->userAgent();
        } catch (\Throwable) {
            return null;
        }
    }
}
