<?php

declare(strict_types=1);

namespace Modules\Core\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Constants\SchemaColumns;

trait HasTenantScope
{
    public function scopeForTenant(Builder $query, int|string $tenantId): Builder
    {
        return $query->where($this->qualifyColumn(SchemaColumns::TENANT_ID), $tenantId);
    }
}
