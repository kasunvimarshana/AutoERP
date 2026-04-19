<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits;

trait HasTenant
{
    protected static function bootHasTenant(): void
    {
        static::addGlobalScope('tenant', function ($builder): void {
            if ($tenantId = auth()->user()?->tenant_id ?? request()->header('X-Tenant-ID')) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model): void {
            if (empty($model->tenant_id) && ($tenantId = auth()->user()?->tenant_id ?? request()->header('X-Tenant-ID'))) {
                $model->tenant_id = $tenantId;
            }
        });
    }
}