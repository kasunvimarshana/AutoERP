<?php

declare(strict_types=1);

namespace Modules\Core\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Constants\SchemaColumns;

trait HasStatusScope
{
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where($this->qualifyColumn(SchemaColumns::STATUS), $status);
    }
}
