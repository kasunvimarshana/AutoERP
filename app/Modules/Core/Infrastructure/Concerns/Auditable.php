<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Concerns;

use Modules\Core\Application\Contracts\AuditServiceInterface;
use Modules\Core\Domain\Entities\AuditLog;
use Modules\Core\Domain\ValueObjects\AuditAction;

/**
 * Application-layer trait for services that need to record custom audit events.
 *
 * Unlike the Eloquent-level {@see HasAudit} trait (which is automatic), this
 * trait is intended for explicit, intentional audit recording inside service
 * classes – for example when recording business-logic actions that do not map
 * directly to a single model mutation.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 *
 *   class TransferFundsService extends BaseService
 *   {
 *       use Auditable;
 *
 *       protected function handle(array $data): mixed
 *       {
 *           // … business logic …
 *
 *           $this->recordAudit(
 *               event: 'funds_transferred',
 *               auditableType: AccountModel::class,
 *               auditableId: $account->getId(),
 *               oldValues: ['balance' => $account->getBalance()],
 *               newValues: ['balance' => $account->getBalance() - $data['amount']],
 *               metadata: ['reference' => $data['reference']],
 *           );
 *
 *           return $result;
 *       }
 *   }
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
trait Auditable
{
    /**
     * Record a custom audit event.
     *
     * @param  string  $event         The action name (use {@see AuditAction} constants or a custom string).
     * @param  string  $auditableType Fully-qualified model/entity class name.
     * @param  int|string  $auditableId  Primary key of the audited resource.
     * @param  array|null  $oldValues  Attribute values before the change.
     * @param  array|null  $newValues  Attribute values after the change.
     * @param  int|null    $tenantId   Override tenant scope (defaults to the
     *                                 authenticated user's tenant).
     * @param  int|null    $userId     Override actor (defaults to the authenticated user).
     * @param  array|null  $tags       Arbitrary string labels attached to this entry.
     * @param  array|null  $metadata   Arbitrary key/value metadata attached to this entry.
     */
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
                'user_id'        => $userId   ?? $this->resolveAuditUserId(),
                'old_values'     => $oldValues,
                'new_values'     => $newValues,
                'url'            => $this->resolveAuditUrl(),
                'ip_address'     => $this->resolveAuditIpAddress(),
                'user_agent'     => $this->resolveAuditUserAgent(),
                'tags'           => $tags,
                'metadata'       => $metadata,
            ]);
        } catch (\Throwable) {
            // Audit failures must never surface to end users.
            return null;
        }
    }

    /**
     * Resolve the AuditServiceInterface from the container.
     */
    protected function resolveAuditService(): ?AuditServiceInterface
    {
        try {
            return app(AuditServiceInterface::class);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve the current authenticated user's ID.
     */
    protected function resolveAuditUserId(): ?int
    {
        try {
            return auth()->id();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve the current authenticated user's tenant ID.
     */
    protected function resolveAuditTenantId(): ?int
    {
        try {
            return auth()->user()?->tenant_id ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve the current request URL (or 'console' when running CLI).
     */
    protected function resolveAuditUrl(): ?string
    {
        try {
            return app()->runningInConsole() ? 'console' : request()->fullUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve the client IP address.
     */
    protected function resolveAuditIpAddress(): ?string
    {
        try {
            return app()->runningInConsole() ? null : request()->ip();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve the User-Agent header.
     */
    protected function resolveAuditUserAgent(): ?string
    {
        try {
            return app()->runningInConsole() ? null : request()->userAgent();
        } catch (\Throwable) {
            return null;
        }
    }
}
