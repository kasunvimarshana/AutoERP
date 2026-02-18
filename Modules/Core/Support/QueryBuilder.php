<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Query Builder Helper
 *
 * Provides filtering, sorting, and including relationships for API queries
 * using native Laravel query builder features.
 *
 * This replaces external query builder packages with a native implementation.
 *
 * Usage:
 * $query = Product::query();
 * $builder = new QueryBuilder($query, $request);
 * $results = $builder
 *     ->allowedFilters(['name', 'category', 'status'])
 *     ->allowedSorts(['name', 'created_at', 'price'])
 *     ->allowedIncludes(['category', 'reviews'])
 *     ->paginate();
 *
 * Request examples:
 * GET /api/products?filter[status]=active&filter[category]=electronics
 * GET /api/products?sort=-created_at
 * GET /api/products?include=category,reviews
 */
class QueryBuilder
{
    protected Builder $query;

    protected Request $request;

    protected array $allowedFilters = [];

    protected array $allowedSorts = [];

    protected array $allowedIncludes = [];

    /**
     * Create a new query builder instance.
     */
    public function __construct(Builder $query, Request $request)
    {
        $this->query = $query;
        $this->request = $request;
    }

    /**
     * Set allowed filter fields.
     *
     * @param  array<string>  $filters
     */
    public function allowedFilters(array $filters): self
    {
        $this->allowedFilters = $filters;
        $this->applyFilters();

        return $this;
    }

    /**
     * Set allowed sort fields.
     *
     * @param  array<string>  $sorts
     */
    public function allowedSorts(array $sorts): self
    {
        $this->allowedSorts = $sorts;
        $this->applySorts();

        return $this;
    }

    /**
     * Set allowed relationships to include.
     *
     * @param  array<string>  $includes
     */
    public function allowedIncludes(array $includes): self
    {
        $this->allowedIncludes = $includes;
        $this->applyIncludes();

        return $this;
    }

    /**
     * Apply filters from request.
     */
    protected function applyFilters(): void
    {
        $filters = $this->request->input('filter', []);

        foreach ($filters as $field => $value) {
            if (in_array($field, $this->allowedFilters)) {
                if (is_array($value)) {
                    $this->query->whereIn($field, $value);
                } else {
                    $this->query->where($field, $value);
                }
            }
        }
    }

    /**
     * Apply sorts from request.
     */
    protected function applySorts(): void
    {
        $sort = $this->request->input('sort');

        if (! $sort) {
            return;
        }

        $sorts = explode(',', $sort);

        foreach ($sorts as $sortField) {
            $direction = 'asc';

            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            if (in_array($sortField, $this->allowedSorts)) {
                $this->query->orderBy($sortField, $direction);
            }
        }
    }

    /**
     * Apply includes from request.
     */
    protected function applyIncludes(): void
    {
        $include = $this->request->input('include');

        if (! $include) {
            return;
        }

        $includes = explode(',', $include);
        $validIncludes = array_intersect($includes, $this->allowedIncludes);

        if (! empty($validIncludes)) {
            $this->query->with($validIncludes);
        }
    }

    /**
     * Get paginated results.
     */
    public function paginate(?int $perPage = null)
    {
        $perPage = $perPage ?? $this->request->input('per_page', 15);

        return $this->query->paginate($perPage);
    }

    /**
     * Get all results.
     */
    public function get()
    {
        return $this->query->get();
    }

    /**
     * Get the underlying query builder.
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }
}
