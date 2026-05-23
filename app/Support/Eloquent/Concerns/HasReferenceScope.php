<?php

declare(strict_types=1);

namespace App\Support\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasReferenceScope
{
    public function scopeReference(Builder $query, string $reference): Builder
    {
        return $query->where($this->qualifyColumn(static::$referenceColumn), $reference);
    }
}
