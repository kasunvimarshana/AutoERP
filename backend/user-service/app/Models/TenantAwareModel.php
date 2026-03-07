<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

abstract class TenantAwareModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model) {
            if (app()->has('current_tenant_id') && empty($model->tenant_id)) {
                $model->tenant_id = app('current_tenant_id');
            }
        });
    }
}
