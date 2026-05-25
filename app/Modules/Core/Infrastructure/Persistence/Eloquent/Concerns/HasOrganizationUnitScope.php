<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Infrastructure\Persistence\Eloquent\Constants\SchemaColumns;

trait HasOrganizationUnitScope
{
    public function scopeForOrganizationUnit(Builder $query, int|string $organizationUnitId): Builder
    {
        return $query->where($this->qualifyColumn(SchemaColumns::ORGANIZATION_UNIT_ID), $organizationUnitId);
    }
}
