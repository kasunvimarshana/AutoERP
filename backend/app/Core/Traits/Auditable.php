<?php

namespace App\Core\Traits;

use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     *
     * @return void
     */
    protected static function bootAuditable()
    {
        static::creating(function (Model $model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function (Model $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function (Model $model) {
            if (auth()->check() && in_array('deleted_by', $model->getFillable())) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }
}
