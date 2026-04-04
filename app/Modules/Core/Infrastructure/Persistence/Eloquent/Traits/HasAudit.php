<?php

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasAudit
{
    public static function bootHasAudit(): void
    {
        static::creating(function (Model $model) {
            if (auth()->check()) {
                $model->created_by = $model->created_by ?? auth()->id();
            }
        });

        static::updating(function (Model $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function (Model $model) {
            if (auth()->check() && method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }
}
