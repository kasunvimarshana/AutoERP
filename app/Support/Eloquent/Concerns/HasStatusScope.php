<?php

declare(strict_types=1);

namespace App\Support\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasStatusScope
{
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where($this->qualifyColumn('status'), $status);
    }
}
