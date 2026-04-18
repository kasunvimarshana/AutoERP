<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Modules\Audit\Application\Contracts\AuditServiceInterface;
use Modules\Audit\Domain\ValueObjects\AuditAction;

trait HasAudit
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private static array $_auditSnapshots = [];

    /**
     * @var array<string, bool>
     */
    private static array $_auditEnabled = [];

    protected static function bootHasAudit(): void
    {
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

        if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model) {
                /** @var self $model */
                $model->auditModelEvent(AuditAction::RESTORED);
                unset(static::$_auditSnapshots[spl_object_id($model)]);
            });
        }
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(
            \Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel::class,
            'auditable'
        );
    }

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

    public function getAuditExclude(): array
    {
        $modelExclude = property_exists($this, 'auditExclude') ? ($this->auditExclude ?? []) : [];

        return array_unique(array_merge(
            ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'],
            $modelExclude,
        ));
    }

    public function getAuditInclude(): array
    {
        return property_exists($this, 'auditInclude') ? ($this->auditInclude ?? []) : [];
    }

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

    protected function shouldAuditEvent(string $eventName): bool
    {
        if ((static::$_auditEnabled[static::class] ?? true) === false) {
            return false;
        }

        $events = property_exists($this, 'auditEvents')
            ? ($this->auditEvents ?? [AuditAction::CREATED, AuditAction::UPDATED, AuditAction::DELETED, AuditAction::RESTORED])
            : [AuditAction::CREATED, AuditAction::UPDATED, AuditAction::DELETED, AuditAction::RESTORED];

        return in_array($eventName, $events, true);
    }

    /**
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

    protected function resolveAuditUserId(): ?int
    {
        try {
            return Auth::id();
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

    protected function resolveAuditService(): ?AuditServiceInterface
    {
        try {
            return app(AuditServiceInterface::class);
        } catch (\Throwable) {
            return null;
        }
    }
}
