<?php

namespace App\Traits;

use App\Models\Audit\AuditLog;
use Illuminate\Support\Str;

/**
 * Trait for models that should be audited
 * Automatically logs creates, updates, and deletes
 */
trait Auditable
{
    /**
     * Boot the auditable trait
     */
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditEvent('created', null, $model->getAuditableAttributes());
        });

        static::updated(function ($model) {
            $model->auditEvent('updated', $model->getOriginal(), $model->getAttributes());
        });

        static::deleted(function ($model) {
            $model->auditEvent('deleted', $model->getOriginal(), null);
        });
    }

    /**
     * Log an audit event
     */
    public function auditEvent(
        string $event,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $tags = [],
        array $metadata = []
    ): void {
        $request = request();

        AuditLog::create([
            'audit_id' => Str::uuid(),
            'user_id' => auth()->id(),
            'tenant_id' => $request->attributes->get('tenant_id'),
            'event' => $event,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'old_values' => $oldValues ? $this->filterAuditableAttributes($oldValues) : null,
            'new_values' => $newValues ? $this->filterAuditableAttributes($newValues) : null,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tags' => $tags,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get attributes that should be audited
     * Override this method in your model to specify which attributes to audit
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();
        
        // Exclude common attributes that shouldn't be audited
        $exclude = array_merge(
            ['password', 'remember_token', 'api_token'],
            $this->getHiddenAuditAttributes()
        );

        return array_diff_key($attributes, array_flip($exclude));
    }

    /**
     * Filter attributes for auditing
     */
    protected function filterAuditableAttributes(array $attributes): array
    {
        $exclude = array_merge(
            ['password', 'remember_token', 'api_token'],
            $this->getHiddenAuditAttributes()
        );

        return array_diff_key($attributes, array_flip($exclude));
    }

    /**
     * Get attributes that should not be audited
     * Override this in your model
     */
    protected function getHiddenAuditAttributes(): array
    {
        return [];
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return AuditLog::where('auditable_type', get_class($this))
            ->where('auditable_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the latest audit log
     */
    public function latestAuditLog()
    {
        return AuditLog::where('auditable_type', get_class($this))
            ->where('auditable_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
