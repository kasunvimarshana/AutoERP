<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\Application\Contracts\AuditServiceInterface;
use Modules\Core\Domain\ValueObjects\AuditAction;

/**
 * Eloquent trait that automatically records audit log entries whenever a model
 * is created, updated, deleted, or restored.
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 *
 *   class AccountModel extends Model
 *   {
 *       use HasAudit;
 *
 *       // (optional) columns that must never appear in the audit log
 *       protected array $auditExclude = ['secret_token'];
 *
 *       // (optional) when set, ONLY these columns will be audited
 *       protected array $auditInclude = ['name', 'status'];
 *
 *       // (optional) disable auditing for specific model events
 *       protected array $auditEvents = ['created', 'updated'];
 *
 *       // (optional) static tags attached to every audit entry
 *       protected array $auditTags = ['finance'];
 *   }
 *
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * The trait resolves the AuditServiceInterface from the Laravel service
 * container.  If the binding is absent (e.g., in unit-test contexts where the
 * service provider is not loaded) the audit step is silently skipped so that
 * it never interrupts normal model operations.
 *
 * NOTE: Properties ($auditExclude, $auditInclude, $auditEvents, $auditTags)
 * are intentionally NOT declared inside this trait.  Declare them in your
 * concrete model class if you need to override the defaults – this avoids PHP
 * 8.x fatal errors about incompatible property redefinitions between a trait
 * and the class that uses it.
 */
trait HasAudit
{
    /**
     * Per-instance original-attribute snapshot captured just before saving.
     * Stored in a static map keyed by spl_object_id() to avoid declaring a
     * typed instance property in the trait (which would conflict with any
     * property of the same name in the host class).
     *
     * @var array<int, array<string, mixed>>
     */
    private static array $_auditSnapshots = [];

    /**
     * Per-class auditing-enabled flag (used by withoutAudit()).
     *
     * @var array<string, bool>
     */
    private static array $_auditEnabled = [];

    /**
     * Boot the HasAudit trait.
     */
    protected static function bootHasAudit(): void
    {
        // Capture a snapshot of the original values BEFORE the write operation.
        static::saving(function (Model $model) {
            static::$_auditSnapshots[spl_object_id($model)] = $model->getOriginal();
        });

        static::created(function (Model $model) {
            /** @var self $model */
            $model->auditModelEvent(AuditAction::CREATED);
            unset(static::$_auditSnapshots[spl_object_id($model)]);
        });

        static::updated(function (Model $model) {
            /** @var self $model */
            $model->auditModelEvent(AuditAction::UPDATED);
            unset(static::$_auditSnapshots[spl_object_id($model)]);
        });

        static::deleted(function (Model $model) {
            /** @var self $model */
            $model->auditModelEvent(AuditAction::DELETED);
            unset(static::$_auditSnapshots[spl_object_id($model)]);
        });

        // SoftDeletes fires a "restored" event when a record is un-deleted.
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model) {
                /** @var self $model */
                $model->auditModelEvent(AuditAction::RESTORED);
                unset(static::$_auditSnapshots[spl_object_id($model)]);
            });
        }
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Return the audit log entries for this model instance.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(
            \Modules\Core\Infrastructure\Persistence\Eloquent\Models\AuditLogModel::class,
            'auditable'
        );
    }

    /**
     * Temporarily disable auditing for the duration of the given callback.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withoutAudit(callable $callback): mixed
    {
        $class = static::class;
        $previous = static::$_auditEnabled[$class] ?? true;
        static::$_auditEnabled[$class] = false;

        try {
            return $callback();
        } finally {
            static::$_auditEnabled[$class] = $previous;
        }
    }

    // ── Column filtering ──────────────────────────────────────────────────────

    /**
     * Return the list of columns that must never appear in the audit payload.
     * Merges the framework defaults with the model-defined $auditExclude list.
     */
    public function getAuditExclude(): array
    {
        $modelExclude = property_exists($this, 'auditExclude') ? ($this->auditExclude ?? []) : [];

        return array_unique(array_merge(
            ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'],
            $modelExclude,
        ));
    }

    /**
     * Return the explicit inclusion list, or an empty array meaning "audit all".
     */
    public function getAuditInclude(): array
    {
        return property_exists($this, 'auditInclude') ? ($this->auditInclude ?? []) : [];
    }

    /**
     * Filter an attribute array down to the auditable columns.
     */
    public function filterAuditableAttributes(array $attributes): array
    {
        $include = $this->getAuditInclude();
        $exclude = $this->getAuditExclude();

        if (! empty($include)) {
            $attributes = array_intersect_key($attributes, array_flip($include));
        }

        foreach ($exclude as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /**
     * Build and persist the audit entry for the given Eloquent event.
     */
    protected function auditModelEvent(string $eventName): void
    {
        if (! $this->shouldAuditEvent($eventName)) {
            return;
        }

        $service = $this->resolveAuditService();
        if ($service === null) {
            return;
        }

        [$oldValues, $newValues] = $this->buildAuditPayload($eventName);

        $modelTags = property_exists($this, 'auditTags') ? ($this->auditTags ?: null) : null;

        try {
            $service->record([
                'event'          => $eventName,
                'auditable_type' => static::class,
                'auditable_id'   => (string) $this->getKey(),
                'tenant_id'      => $this->getAttribute('tenant_id'),
                'user_id'        => $this->resolveAuditUserId(),
                'old_values'     => $oldValues,
                'new_values'     => $newValues,
                'url'            => $this->resolveAuditUrl(),
                'ip_address'     => $this->resolveAuditIpAddress(),
                'user_agent'     => $this->resolveAuditUserAgent(),
                'tags'           => $modelTags,
                'metadata'       => null,
            ]);
        } catch (\Throwable) {
            // Audit failures must never surface to end users.
        }
    }

    /**
     * Determine whether the given event should be audited.
     */
    protected function shouldAuditEvent(string $eventName): bool
    {
        // Respect the per-class disable flag set by withoutAudit().
        if ((static::$_auditEnabled[static::class] ?? true) === false) {
            return false;
        }

        $events = property_exists($this, 'auditEvents')
            ? ($this->auditEvents ?? [AuditAction::CREATED, AuditAction::UPDATED, AuditAction::DELETED, AuditAction::RESTORED])
            : [AuditAction::CREATED, AuditAction::UPDATED, AuditAction::DELETED, AuditAction::RESTORED];

        return in_array($eventName, $events, true);
    }

    /**
     * Build the old / new values arrays for the audit entry.
     *
     * @return array{0: array|null, 1: array|null}
     */
    protected function buildAuditPayload(string $eventName): array
    {
        $snapshot = static::$_auditSnapshots[spl_object_id($this)] ?? [];

        $oldValues = null;
        $newValues = null;

        switch ($eventName) {
            case AuditAction::CREATED:
                $newValues = $this->filterAuditableAttributes($this->getAttributes());
                break;

            case AuditAction::UPDATED:
                $oldValues = $this->filterAuditableAttributes($snapshot);
                $newValues = $this->filterAuditableAttributes($this->getAttributes());
                break;

            case AuditAction::DELETED:
                $oldValues = $this->filterAuditableAttributes($this->getAttributes());
                break;

            case AuditAction::RESTORED:
                $newValues = $this->filterAuditableAttributes($this->getAttributes());
                break;
        }

        return [$oldValues, $newValues];
    }

    /**
     * Attempt to resolve the ID of the currently authenticated user.
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
     * Attempt to resolve the current request URL.
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
     * Attempt to resolve the client IP address.
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
     * Attempt to resolve the client User-Agent header.
     */
    protected function resolveAuditUserAgent(): ?string
    {
        try {
            return app()->runningInConsole() ? null : request()->userAgent();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Safely resolve the AuditServiceInterface from the container.
     */
    protected function resolveAuditService(): ?AuditServiceInterface
    {
        try {
            return app(AuditServiceInterface::class);
        } catch (\Throwable) {
            return null;
        }
    }
}
