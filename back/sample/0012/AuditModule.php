<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\Entities;

/**
 * AuditLog Domain Entity
 * Immutable — created once, never mutated.
 */
final class AuditLog
{
    public function __construct(
        private readonly int    $tenantId,
        private readonly string $auditableType,
        private readonly int    $auditableId,
        private readonly string $event,
        private readonly string $module,
        private readonly ?int   $userId       = null,
        private readonly ?string $userType    = null,
        private readonly ?array  $oldValues   = null,
        private readonly ?array  $newValues   = null,
        private readonly ?array  $changedFields = null,
        private readonly ?array  $metadata    = null,
        private readonly ?string $url         = null,
        private readonly ?string $ipAddress   = null,
        private readonly ?string $userAgent   = null,
        private readonly ?string $requestId   = null,
        private readonly ?string $tags        = null,
        private readonly ?\DateTimeInterface $occurredAt = null,
        private readonly ?int   $id           = null,
    ) {}

    public function getId(): ?int               { return $this->id; }
    public function getTenantId(): int          { return $this->tenantId; }
    public function getAuditableType(): string  { return $this->auditableType; }
    public function getAuditableId(): int       { return $this->auditableId; }
    public function getEvent(): string          { return $this->event; }
    public function getModule(): string         { return $this->module; }
    public function getUserId(): ?int           { return $this->userId; }
    public function getOldValues(): ?array      { return $this->oldValues; }
    public function getNewValues(): ?array      { return $this->newValues; }
    public function getChangedFields(): ?array  { return $this->changedFields; }
    public function getOccurredAt(): ?\DateTimeInterface { return $this->occurredAt; }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'tenant_id'      => $this->tenantId,
            'auditable_type' => $this->auditableType,
            'auditable_id'   => $this->auditableId,
            'event'          => $this->event,
            'module'         => $this->module,
            'user_id'        => $this->userId,
            'user_type'      => $this->userType,
            'old_values'     => $this->oldValues,
            'new_values'     => $this->newValues,
            'changed_fields' => $this->changedFields,
            'metadata'       => $this->metadata,
            'url'            => $this->url,
            'ip_address'     => $this->ipAddress,
            'user_agent'     => $this->userAgent,
            'request_id'     => $this->requestId,
            'tags'           => $this->tags,
            'occurred_at'    => $this->occurredAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}


namespace Modules\Audit\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Modules\Audit\Domain\Entities\AuditLog;

/**
 * AuditService
 *
 * Central audit writer. All domain events route here to be persisted
 * in the immutable audit_logs table.
 *
 * Usage:
 *   app(AuditService::class)->record(
 *       tenantId: 1,
 *       auditableType: 'product',
 *       auditableId: 42,
 *       event: 'updated',
 *       module: 'Product',
 *       oldValues: [...],
 *       newValues: [...],
 *   );
 */
final class AuditService
{
    public function record(
        int    $tenantId,
        string $auditableType,
        int    $auditableId,
        string $event,
        string $module,
        ?array $oldValues     = null,
        ?array $newValues     = null,
        ?array $changedFields = null,
        ?int   $userId        = null,
        ?string $userType     = null,
        ?string $tags         = null,
    ): void {
        // Never throw — audit must never break the main flow
        try {
            DB::table('audit_logs')->insert([
                'tenant_id'      => $tenantId,
                'auditable_type' => $auditableType,
                'auditable_id'   => $auditableId,
                'event'          => $event,
                'module'         => $module,
                'user_id'        => $userId ?? auth()->id(),
                'user_type'      => $userType ?? (auth()->check() ? 'user' : 'system'),
                'user_name'      => auth()->user()?->name,
                'old_values'     => $oldValues ? json_encode($oldValues) : null,
                'new_values'     => $newValues ? json_encode($newValues) : null,
                'changed_fields' => $changedFields ? json_encode($changedFields) : null,
                'metadata'       => json_encode([
                    'request_id' => Request::header('X-Request-ID'),
                    'session_id' => session()->getId(),
                ]),
                'url'            => Request::fullUrl(),
                'ip_address'     => Request::ip(),
                'user_agent'     => Request::userAgent(),
                'request_id'     => Request::header('X-Request-ID'),
                'tags'           => $tags,
                'occurred_at'    => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } catch (\Throwable) {
            // Silently swallow — audit failure must never break business logic
            logger()->error('AuditService: failed to write audit log', [
                'auditable_type' => $auditableType,
                'auditable_id'   => $auditableId,
                'event'          => $event,
            ]);
        }
    }

    public function getHistory(
        int    $tenantId,
        string $auditableType,
        int    $auditableId,
        int    $perPage = 25,
    ): mixed {
        return DB::table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->orderByDesc('occurred_at')
            ->paginate($perPage);
    }

    public function getByUser(int $tenantId, int $userId, int $perPage = 25): mixed
    {
        return DB::table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderByDesc('occurred_at')
            ->paginate($perPage);
    }

    public function getByModule(int $tenantId, string $module, int $perPage = 25): mixed
    {
        return DB::table('audit_logs')
            ->where('tenant_id', $tenantId)
            ->where('module', $module)
            ->orderByDesc('occurred_at')
            ->paginate($perPage);
    }
}


namespace Modules\Audit\Infrastructure\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Auditable Trait
 *
 * Attach to any Eloquent model for automatic audit logging.
 * Confirmed from KVAutoERP's "all must be auditable" requirement.
 *
 * Usage:
 *   class ProductModel extends Model {
 *       use Auditable;
 *       protected string $auditModule = 'Product';
 *       protected array $auditExclude = ['updated_at'];
 *   }
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn (Model $m) => $m->writeAudit('created', [], $m->toArray()));
        static::updated(fn (Model $m) => $m->writeAudit('updated', $m->getOriginal(), $m->getDirty()));
        static::deleted(fn (Model $m) => $m->writeAudit('deleted', $m->toArray(), []));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $m) => $m->writeAudit('restored', [], $m->toArray()));
        }
    }

    public function auditEvent(string $event, array $old = [], array $new = []): void
    {
        $this->writeAudit($event, $old, $new);
    }

    private function writeAudit(string $event, array $old, array $new): void
    {
        $old = $this->filterAuditFields($old);
        $new = $this->filterAuditFields($new);

        // Skip no-op updates
        if ($event === 'updated' && empty($new)) return;

        $changed = array_keys($new);

        app(\Modules\Audit\Application\Services\AuditService::class)->record(
            tenantId:      $this->tenant_id ?? 0,
            auditableType: static::class,
            auditableId:   (int) $this->getKey(),
            event:         $event,
            module:        $this->auditModule ?? class_basename(static::class),
            oldValues:     $old ?: null,
            newValues:     $new ?: null,
            changedFields: $changed ?: null,
            tags:          implode(',', $this->auditTags ?? []),
        );
    }

    private function filterAuditFields(array $values): array
    {
        $exclude = array_merge(
            $this->auditExclude ?? [],
            $this->auditTimestamps ?? true ? [] : ['created_at', 'updated_at', 'deleted_at'],
        );

        foreach ($exclude as $field) {
            unset($values[$field]);
        }

        if (!empty($this->auditInclude)) {
            $values = array_intersect_key($values, array_flip($this->auditInclude));
        }

        return $values;
    }
}
