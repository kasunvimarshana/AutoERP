<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (App::has('tenant')) {
                $builder->where(
                    (new static)->getTable() . '.tenant_id',
                    App::make('tenant')->id
                );
            }
        });

        static::creating(function ($model) {
            if (App::has('tenant') && empty($model->tenant_id)) {
                $model->tenant_id = App::make('tenant')->id;
            }
        });
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Tenant::class);
    }
}
