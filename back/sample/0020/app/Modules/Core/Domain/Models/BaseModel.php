<?php

namespace App\Modules\Core\Domain\Models;

use App\Modules\Core\Domain\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model {
    protected static function booted() {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (auth()->check() && !isset($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
