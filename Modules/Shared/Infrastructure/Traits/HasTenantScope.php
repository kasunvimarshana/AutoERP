<?php
namespace Modules\Shared\Infrastructure\Traits;
use Modules\Shared\Infrastructure\Scopes\TenantScope;
trait HasTenantScope
{
    public static function bootHasTenantScope(): void
    {
        static::addGlobalScope(new TenantScope());
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenantId = app('current.tenant.id');
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }
}
