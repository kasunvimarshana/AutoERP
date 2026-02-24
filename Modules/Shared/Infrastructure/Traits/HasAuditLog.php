<?php
namespace Modules\Shared\Infrastructure\Traits;
trait HasAuditLog
{
    public static function bootHasAuditLog(): void
    {
        static::creating(function ($model) {
            $userId = auth()->id();
            if ($userId && isset($model->created_by)) {
                $model->created_by = $userId;
            }
        });
        static::updating(function ($model) {
            $userId = auth()->id();
            if ($userId && isset($model->updated_by)) {
                $model->updated_by = $userId;
            }
        });
    }
}
