<?php

namespace Modules\Core\Traits;

use Modules\Core\Models\AuditLog;
use Modules\Core\Services\AuditService;

trait HasAudit
{
    protected static function bootHasAudit()
    {
        static::created(function ($model) {
            app(AuditService::class)->log($model, 'created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            if ($model->wasChanged()) {
                app(AuditService::class)->log(
                    $model,
                    'updated',
                    $model->getOriginal(),
                    $model->getAttributes()
                );
            }
        });

        static::deleted(function ($model) {
            $event = $model->isForceDeleting() ? 'deleted' : 'soft_deleted';
            app(AuditService::class)->log($model, $event, $model->getAttributes(), []);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                app(AuditService::class)->log($model, 'restored', [], $model->getAttributes());
            });
        }
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function getAuditExclude(): array
    {
        return property_exists($this, 'auditExclude') ? $this->auditExclude : [];
    }

    public function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();
        $exclude = array_merge(
            $this->getAuditExclude(),
            ['updated_at', 'deleted_at']
        );

        return array_diff_key($attributes, array_flip($exclude));
    }
}
