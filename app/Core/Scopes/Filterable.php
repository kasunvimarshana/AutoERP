<?php

declare(strict_types=1);

namespace App\Core\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Filterable Trait
 *
 * Adds dynamic filtering capabilities to Eloquent models
 */
trait Filterable
{
    /**
     * Scope a query to apply filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if (method_exists($this, $method = 'filter'.ucfirst($key))) {
                $this->$method($query, $value);
            } elseif ($value !== null && $value !== '') {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    /**
     * Scope a query to search across specified columns
     *
     * @param  array<string>  $columns
     */
    public function scopeSearch(Builder $query, ?string $search, array $columns): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Scope a query to apply simple sorting
     *
     * Note: For models that need whitelisted columns, use the Sortable trait instead
     */
    public function scopeSort(Builder $query, ?string $column, string $direction = 'asc'): Builder
    {
        if (! $column) {
            return $query;
        }

        // Validate direction
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        // Only allow sorting if column exists in table or is a valid expression
        // This prevents SQL injection through column names
        $allowedColumns = method_exists($this, 'getSortableColumns')
            ? $this->getSortableColumns()
            : [];

        if (! empty($allowedColumns) && ! in_array($column, $allowedColumns, true)) {
            return $query;
        }

        return $query->orderBy($column, $direction);
    }

    /**
     * Scope a query to filter by date range
     */
    public function scopeDateRange(Builder $query, string $column, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }
}
