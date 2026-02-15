<?php

namespace App\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait TenantScoped
{
    /**
     * Boot the tenant scoped trait for a model.
     *
     * @return void
     */
    protected static function bootTenantScoped()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
