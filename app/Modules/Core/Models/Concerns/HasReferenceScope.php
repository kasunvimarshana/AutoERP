<?php

declare(strict_types=1);

namespace Modules\Core\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Constants\SchemaColumns;

trait HasReferenceScope
{
    public function scopeReference(Builder $query, string $reference): Builder
    {
        return $query->where($this->qualifyColumn($this->referenceColumnName()), $reference);
    }

    protected function referenceColumnName(): string
    {
        if (property_exists($this, 'referenceColumn') && is_string($this->referenceColumn)) {
            return $this->referenceColumn;
        }

        return SchemaColumns::REFERENCE;
    }
}
