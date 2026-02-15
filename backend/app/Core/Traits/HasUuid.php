<?php

namespace App\Core\Traits;

trait HasUuid
{
    /**
     * Boot the UUID trait for a model.
     *
     * @return void
     */
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
