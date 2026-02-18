<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Services\TenantContext;

trait HasTenant
{
    protected static function bootHasTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app(TenantContext::class)->getTenantId();

            if ($tenantId) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (! isset($model->tenant_id)) {
                $tenantId = app(TenantContext::class)->getTenantId();
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\Modules\Core\Models\Tenant::class);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->withoutGlobalScope('tenant')
            ->where($this->getTable().'.tenant_id', $tenantId);
    }

    public function scopeAllTenants($query)
    {
        return $query->withoutGlobalScope('tenant');
    }
}
