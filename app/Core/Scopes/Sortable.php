<?php

declare(strict_types=1);

namespace App\Core\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Sortable Trait
 * 
 * Adds sorting capabilities with mandatory column whitelist to Eloquent models
 * Use this trait when you need to explicitly define which columns can be sorted
 * For simpler sorting without whitelist, use the scopeSort method from Filterable trait
 */
trait Sortable
{
    /**
     * Allowed sortable columns
     * Must be implemented by the model using this trait
     *
     * @return array<string>
     */
    abstract protected function getSortableColumns(): array;

    /**
     * Scope a query to apply sorting
     *
     * @param Builder $query
     * @param string|null $column
     * @param string $direction
     * @return Builder
     */
    public function scopeSortBy(Builder $query, ?string $column, string $direction = 'asc'): Builder
    {
        if (!$column || !in_array($column, $this->getSortableColumns(), true)) {
            return $query;
        }

        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($column, $direction);
    }

    /**
     * Scope for multiple sort columns
     *
     * @param Builder $query
     * @param array<array{column: string, direction: string}> $sorts
     * @return Builder
     */
    public function scopeMultiSort(Builder $query, array $sorts): Builder
    {
        foreach ($sorts as $sort) {
            if (isset($sort['column']) && in_array($sort['column'], $this->getSortableColumns(), true)) {
                $direction = strtolower($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
                $query->orderBy($sort['column'], $direction);
            }
        }

        return $query;
    }
}
