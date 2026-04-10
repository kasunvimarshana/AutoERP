<?php

namespace App\Modules\Core\Traits;

use App\Modules\Audit\Models\AuditLog;

trait HasAudit
{
    protected static function bootHasAudit()
    {
        static::created(function ($model) {
            $model->logAudit('created');
        });
        
        static::updated(function ($model) {
            $model->logAudit('updated');
        });
        
        static::deleted(function ($model) {
            $model->logAudit('deleted');
        });
    }

    protected function logAudit($event)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'old_values' => $this->getOriginal(),
            'new_values' => $this->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}