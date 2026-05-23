<?php

declare(strict_types=1);

namespace App\Support\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasTenantScope
{
    public function scopeForTenant(Builder $query, int|string $tenantId): Builder
    {
        return $query->where($this->qualifyColumn('tenant_id'), $tenantId);
    }
}
