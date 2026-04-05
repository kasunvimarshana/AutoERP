<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Traits;

trait HasTenant
{
    protected static function bootHasTenant()
    {
        static::addGlobalScope('tenant', function ($builder) {
            if ($tenantId = auth()->user()?->tenant_id ?? request()->header('X-Tenant-ID')) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->tenant_id) && ($tenantId = auth()->user()?->tenant_id ?? request()->header('X-Tenant-ID'))) {
                $model->tenant_id = $tenantId;
            }
        });
    }
}
