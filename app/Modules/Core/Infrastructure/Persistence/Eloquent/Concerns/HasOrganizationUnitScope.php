<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasOrganizationUnitScope
{
    public function scopeForOrganizationUnit(Builder $query, int|string $organizationUnitId): Builder
    {
        return $query->where($this->qualifyColumn('organization_unit_id'), $organizationUnitId);
    }
}
