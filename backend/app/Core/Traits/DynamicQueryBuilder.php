<?php

namespace App\Core\Traits;

use App\DTOs\QueryConfig;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dynamic Query Builder Trait
 * 
 * Provides configuration-driven query building capabilities
 * for advanced filtering, searching, sorting, and eager loading
 * 
 * @package App\Core\Traits
 */
trait DynamicQueryBuilder
{
    /**
     * Apply QueryConfig to the query builder
     *
     * @param Builder $query
     * @param QueryConfig $config
     * @return Builder
     */
    protected function applyQueryConfig(Builder $query, QueryConfig $config): Builder
    {
        // Apply tenant scoping
        if ($config->tenantId !== null) {
            $query->where('tenant_id', $config->tenantId);
        }

        // Apply field selection
        if ($config->select !== ['*']) {
            $query->select($config->select);
        }

        // Apply global search
        if ($config->search) {
            $this->applySearch($query, $config->search);
        }

        // Apply filters
        if (!empty($config->filters)) {
            $this->applyFilters($query, $config->filters);
        }

        // Apply relation filters
        if (!empty($config->relationFilters)) {
            $this->applyRelationFilters($query, $config->relationFilters);
        }

        // Apply sorting
        if (!empty($config->sort)) {
            $this->applySorting($query, $config->sort);
        }

        // Apply eager loading
        if (!empty($config->with)) {
            $query->with($this->parseWithRelations($config->with));
        }

        // Apply relation counts
        if (!empty($config->withCount)) {
            $query->withCount($config->withCount);
        }

        return $query;
    }

    /**
     * Apply global search across multiple fields
     *
     * @param Builder $query
     * @param array $search ['query' => '...', 'fields' => [...]]
     * @return void
     */
    protected function applySearch(Builder $query, array $search): void
    {
        if (empty($search['query']) || empty($search['fields'])) {
            return;
        }

        $searchTerm = $search['query'];
        $fields = $search['fields'];

        $query->where(function ($q) use ($searchTerm, $fields) {
            foreach ($fields as $field) {
                // Support nested relation search (e.g., 'user.name')
                if (str_contains($field, '.')) {
                    $this->applyNestedSearch($q, $field, $searchTerm);
                } else {
                    $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                }
            }
        });
    }

    /**
     * Apply nested relation search
     *
     * @param Builder $query
     * @param string $field Format: 'relation.field'
     * @param string $searchTerm
     * @return void
     */
    protected function applyNestedSearch(Builder $query, string $field, string $searchTerm): void
    {
        $parts = explode('.', $field);
        $relation = $parts[0];
        $relationField = $parts[1];

        $query->orWhereHas($relation, function ($q) use ($relationField, $searchTerm) {
            $q->where($relationField, 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Apply filters to the query
     *
     * Supports multiple operators: =, !=, >, <, >=, <=, LIKE, IN, NOT IN, BETWEEN, IS NULL, IS NOT NULL
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                // Advanced filter with operator
                $this->applyAdvancedFilter($query, $field, $value);
            } else {
                // Simple equality filter
                $query->where($field, '=', $value);
            }
        }
    }

    /**
     * Apply advanced filter with operator
     *
     * @param Builder $query
     * @param string $field
     * @param array $filter ['operator' => '...', 'value' => '...']
     * @return void
     */
    protected function applyAdvancedFilter(Builder $query, string $field, array $filter): void
    {
        $operator = strtoupper($filter['operator'] ?? '=');
        $value = $filter['value'] ?? null;

        switch ($operator) {
            case 'IN':
                $query->whereIn($field, is_array($value) ? $value : [$value]);
                break;

            case 'NOT IN':
                $query->whereNotIn($field, is_array($value) ? $value : [$value]);
                break;

            case 'BETWEEN':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($field, $value);
                }
                break;

            case 'NOT BETWEEN':
                if (is_array($value) && count($value) === 2) {
                    $query->whereNotBetween($field, $value);
                }
                break;

            case 'IS NULL':
                $query->whereNull($field);
                break;

            case 'IS NOT NULL':
                $query->whereNotNull($field);
                break;

            case 'LIKE':
                $query->where($field, 'LIKE', "%{$value}%");
                break;

            case 'STARTS WITH':
                $query->where($field, 'LIKE', "{$value}%");
                break;

            case 'ENDS WITH':
                $query->where($field, 'LIKE', "%{$value}");
                break;

            default:
                // Standard operators: =, !=, >, <, >=, <=
                $query->where($field, $operator, $value);
                break;
        }
    }

    /**
     * Apply relation-based filters
     *
     * Format: ['relation.field' => 'value']
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyRelationFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (!str_contains($field, '.')) {
                continue;
            }

            $parts = explode('.', $field);
            $relation = $parts[0];
            $relationField = $parts[1];

            $query->whereHas($relation, function ($q) use ($relationField, $value) {
                if (is_array($value)) {
                    $this->applyAdvancedFilter($q, $relationField, $value);
                } else {
                    $q->where($relationField, '=', $value);
                }
            });
        }
    }

    /**
     * Apply multi-field sorting
     *
     * @param Builder $query
     * @param array $sort ['field' => 'direction'] or [['field' => '...', 'direction' => '...']]
     * @return void
     */
    protected function applySorting(Builder $query, array $sort): void
    {
        foreach ($sort as $field => $direction) {
            if (is_array($direction)) {
                // Array format: [['field' => '...', 'direction' => '...']]
                $field = $direction['field'] ?? $field;
                $dir = strtolower($direction['direction'] ?? 'asc');
            } else {
                // Simple format: ['field' => 'direction']
                $dir = strtolower($direction);
            }

            // Validate direction
            $dir = in_array($dir, ['asc', 'desc']) ? $dir : 'asc';

            // Support relation sorting (e.g., 'user.name')
            if (str_contains($field, '.')) {
                // Relation sorting requires table joins which is not supported in this generic implementation
                // Skip for now - implement in specific repositories if needed
                // Consider using query scopes or custom repository methods for complex relation sorting
                continue;
            }

            $query->orderBy($field, $dir);
        }
    }

    /**
     * Parse with relations to support field selection
     *
     * Format: ['relation', 'relation:field1,field2']
     *
     * @param array $relations
     * @return array
     */
    protected function parseWithRelations(array $relations): array
    {
        $parsed = [];

        foreach ($relations as $relation) {
            if (str_contains($relation, ':')) {
                // Relation with field selection: 'relation:field1,field2'
                [$relationName, $fields] = explode(':', $relation);
                $fieldArray = explode(',', $fields);
                
                $parsed[$relationName] = function ($query) use ($fieldArray) {
                    $query->select($fieldArray);
                };
            } else {
                // Simple relation
                $parsed[] = $relation;
            }
        }

        return $parsed;
    }

    /**
     * Build dynamic query from QueryConfig
     *
     * @param Builder $query
     * @param QueryConfig $config
     * @return Builder|\Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    protected function buildDynamicQuery(Builder $query, QueryConfig $config)
    {
        // Apply all query configurations
        $query = $this->applyQueryConfig($query, $config);

        // Return paginated or all results
        if ($config->paginate && $config->perPage !== null) {
            return $query->paginate($config->perPage, ['*'], 'page', $config->page);
        } elseif ($config->paginate) {
            return $query->paginate(15); // Default pagination
        } else {
            return $query->get();
        }
    }
}
