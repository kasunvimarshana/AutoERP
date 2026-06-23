<?php

declare(strict_types=1);

namespace Modules\Core\Models\Concerns;

use Modules\Core\Database\Scopes\TenantScope;

trait HasTenantScope
{
    protected static function bootHasTenantScope(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
