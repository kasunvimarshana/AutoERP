<?php

declare(strict_types=1);

namespace Modules\Core\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Constants\SchemaColumns;

trait HasActiveScope
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn(SchemaColumns::IS_ACTIVE), true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn(SchemaColumns::IS_ACTIVE), false);
    }
}
