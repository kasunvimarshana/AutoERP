<?php

declare(strict_types=1);

namespace Modules\Core\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Constants\SchemaColumns;

trait HasOrganizationUnitScope
{
    public function scopeForOrganizationUnit(Builder $query, int|string $organizationUnitId): Builder
    {
        return $query->where($this->qualifyColumn(SchemaColumns::ORGANIZATION_UNIT_ID), $organizationUnitId);
    }
}
