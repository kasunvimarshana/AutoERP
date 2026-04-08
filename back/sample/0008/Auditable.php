<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Auditable Trait
 *
 * Attach to any Eloquent model to automatically log all create/update/delete events
 * into the audit_logs table with full before/after value capture.
 *
 * Usage:
 *   use App\Traits\Auditable;
 *   class Product extends Model { use Auditable; }
 *
 * Customization per model:
 *   protected array $auditInclude = ['name', 'price'];   // only these columns
 *   protected array $auditExclude = ['password', 'token']; // never these columns
 *   protected array $auditTags = ['products', 'catalog'];   // searchable tags
 *   protected bool $auditTimestamps = false;              // skip created_at/updated_at
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn (Model $model)  => $model->recordAudit('created',  [],              $model->toArray()));
        static::updated(fn (Model $model)  => $model->recordAudit('updated',  $model->getOriginal(), $model->getDirty()));
        static::deleted(fn (Model $model)  => $model->recordAudit('deleted',  $model->toArray(), []));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $model) => $model->recordAudit('restored', [], $model->toArray()));
        }
    }

    // ── Custom audit event ──────────────────────────────────────────────────
    public function auditEvent(string $event, array $oldValues = [], array $newValues = [], ?string $tags = null): void
    {
        $this->recordAudit($event, $oldValues, $newValues, $tags);
    }

    // ── Core audit writer ───────────────────────────────────────────────────
    protected function recordAudit(string $event, array $oldValues, array $newValues, ?string $tags = null): void
    {
        $oldValues = $this->filterAuditFields($oldValues);
        $newValues = $this->filterAuditFields($newValues);

        // Skip if nothing meaningful changed (e.g. only timestamps touched)
        if ($event === 'updated' && empty($newValues)) {
            return;
        }

        AuditLog::create([
            'auditable_type'  => static::class,
            'auditable_id'    => $this->getKey(),
            'event'           => $event,
            'user_id'         => Auth::id(),
            'user_type'       => Auth::check() ? get_class(Auth::user()) : 'system',
            'old_values'      => $oldValues ?: null,
            'new_values'      => $newValues ?: null,
            'metadata'        => [
                'request_id' => Request::header('X-Request-ID'),
                'session_id' => session()->getId(),
            ],
            'url'             => Request::fullUrl(),
            'ip_address'      => Request::ip(),
            'user_agent'      => Request::userAgent(),
            'tags'            => $tags ?? implode(',', $this->getAuditTags()),
        ]);
    }

    protected function filterAuditFields(array $values): array
    {
        // Strip excluded columns first
        $excluded = array_merge(
            $this->auditExclude ?? [],
            ($this->auditTimestamps ?? true) ? [] : ['created_at', 'updated_at', 'deleted_at']
        );

        foreach ($excluded as $field) {
            unset($values[$field]);
        }

        // Then apply include filter if defined
        if (!empty($this->auditInclude)) {
            $values = array_intersect_key($values, array_flip($this->auditInclude));
        }

        return $values;
    }

    protected function getAuditTags(): array
    {
        return $this->auditTags ?? [];
    }

    // ── Relationship to audit logs ──────────────────────────────────────────
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    // ── Convenience scopes ──────────────────────────────────────────────────
    public function latestAudit(): ?AuditLog
    {
        return $this->auditLogs()->latest()->first();
    }

    public function createdByUser(): ?int
    {
        return $this->auditLogs()
            ->where('event', 'created')
            ->value('user_id');
    }
}
