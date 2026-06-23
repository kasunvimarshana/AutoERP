<?php

declare(strict_types=1);

namespace Modules\Core\Models\Concerns;

use LogicException;

trait HasImmutableTenantOwnership
{
    protected static function bootHasImmutableTenantOwnership(): void
    {
        static::updating(static function (self $model): void {
            if ($model->isDirty('tenant_id')) {
                throw new LogicException(sprintf(
                    'Tenant ownership of [%s] cannot be changed after creation.',
                    $model->getTable(),
                ));
            }
        });
    }
}
