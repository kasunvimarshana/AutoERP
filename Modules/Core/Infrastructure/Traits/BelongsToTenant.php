<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Traits;

use Modules\Core\Infrastructure\Scopes\TenantScope;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (self $model): void {
            if (empty($model->tenant_id)) {
                $tenantId = app('current.tenant.id');
                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }
}
