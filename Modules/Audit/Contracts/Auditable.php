<?php

declare(strict_types=1);

namespace Modules\Audit\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Auditable Trait
 *
 * Add to models that should be audited
 */
trait Auditable
{
    /**
     * Boot the auditable trait
     */
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $model->logAudit('created');
        });

        static::updated(function (Model $model) {
            $model->logAudit('updated');
        });

        static::deleted(function (Model $model) {
            $model->logAudit('deleted');
        });
    }

    /**
     * Log an audit event
     */
    public function logAudit(string $event, array $metadata = []): void
    {
        $auditService = app(\Modules\Audit\Services\AuditService::class);

        $auditService->log([
            'event' => $event,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'old_values' => $event === 'updated' ? $this->getOriginal() : [],
            'new_values' => $event !== 'deleted' ? $this->getAttributes() : [],
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return $this->morphMany(\Modules\Audit\Models\AuditLog::class, 'auditable');
    }
}
