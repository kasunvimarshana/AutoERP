<?php

declare(strict_types=1);

namespace App\Traits;

use Modules\Core\Services\AuditService;

/**
 * Auditable Model Trait
 *
 * Automatically tracks all model changes (create, update, delete)
 * for compliance and security auditing.
 *
 * Usage: Add `use AuditableModel;` to any Eloquent model
 */
trait AuditableModel
{
    /**
     * Boot the auditable trait
     */
    protected static function bootAuditableModel(): void
    {
        static::created(function ($model) {
            $model->auditCreate();
        });

        static::updated(function ($model) {
            $model->auditUpdate();
        });

        static::deleted(function ($model) {
            $model->auditDelete();
        });
    }

    /**
     * Audit model creation
     */
    protected function auditCreate(): void
    {
        $this->logAuditEvent('created', [
            'attributes' => $this->getAuditableAttributes(),
        ]);
    }

    /**
     * Audit model update
     */
    protected function auditUpdate(): void
    {
        $changes = $this->getChanges();
        $original = collect($this->getOriginal())
            ->only(array_keys($changes))
            ->toArray();

        if (empty($changes)) {
            return; // No changes to audit
        }

        $this->logAuditEvent('updated', [
            'old' => $original,
            'new' => $changes,
        ]);
    }

    /**
     * Audit model deletion
     */
    protected function auditDelete(): void
    {
        $this->logAuditEvent('deleted', [
            'attributes' => $this->getAuditableAttributes(),
        ]);
    }

    /**
     * Log audit event
     */
    protected function logAuditEvent(string $action, array $data): void
    {
        try {
            $auditService = app(AuditService::class);

            $auditService->log(
                entity: get_class($this),
                entityId: $this->getKey(),
                action: $action,
                data: $data,
                userId: auth()->id(),
                ipAddress: request()->ip(),
                userAgent: request()->userAgent()
            );
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            logger()->error('Audit logging failed', [
                'model' => get_class($this),
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get attributes that should be audited
     * Override in model to exclude sensitive fields
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();

        // Exclude sensitive fields by default
        $excluded = ['password', 'remember_token', 'api_token'];

        return collect($attributes)
            ->except($excluded)
            ->toArray();
    }

    /**
     * Get audit trail for this model
     */
    public function auditTrail()
    {
        return $this->morphMany(
            \Modules\Core\Models\AuditLog::class,
            'auditable',
            'entity',
            'entity_id'
        );
    }
}
