<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Abstract base repository providing full CRUD, filtering, searching,
 * sorting, and conditional pagination for all domain repositories.
 */
abstract class BaseRepository
{
    protected Model $model;

    /** Columns allowed for filtering (override in child repositories). */
    protected array $filterable = [];

    /** Columns searched when a search term is provided. */
    protected array $searchable = [];

    /** Default column and direction for sorting. */
    protected string $defaultSortColumn = 'created_at';
    protected string $defaultSortDirection = 'desc';

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Get all records with optional filtering, searching, sorting,
     * and conditional pagination.
     *
     * Returns a paginated result when `per_page` is present in $filters,
     * otherwise returns a plain Collection.
     */
    public function all(array $filters = []): mixed
    {
        $query = $this->model->newQuery();
        $query = $this->applyTenantScope($query);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySearch($query, $filters);
        $query = $this->applySorting($query, $filters);

        return $this->conditionalPaginate($query, $filters);
    }

    /**
     * Find a single record by primary key.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->applyTenantScope($this->model->newQuery())->findOrFail($id);
    }

    /** Find a single record by primary key, or return null. */
    public function find(int|string $id): ?Model
    {
        return $this->applyTenantScope($this->model->newQuery())->find($id);
    }

    /** Find a record matching the given attribute/value pair. */
    public function findBy(string $attribute, mixed $value): ?Model
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->where($attribute, $value)
            ->first();
    }

    /** Find multiple records matching the given attribute/value pair. */
    public function findAllBy(string $attribute, mixed $value): Collection
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->where($attribute, $value)
            ->get();
    }

    /** Create a new record and return the persisted model instance. */
    public function create(array $attributes): Model
    {
        return $this->model->newQuery()->create($attributes);
    }

    /**
     * Update an existing record.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int|string $id, array $attributes): Model
    {
        $record = $this->findOrFail($id);
        $record->update($attributes);

        return $record->fresh();
    }

    /**
     * Delete a record by primary key.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int|string $id): bool
    {
        $record = $this->findOrFail($id);

        return (bool) $record->delete();
    }

    /** Permanently delete a soft-deleted record. */
    public function forceDelete(int|string $id): bool
    {
        /** @var \Illuminate\Database\Eloquent\SoftDeletes $record */
        $record = $this->model->newQuery()->withTrashed()->findOrFail($id);

        return (bool) $record->forceDelete();
    }

    /** Restore a soft-deleted record. */
    public function restore(int|string $id): bool
    {
        /** @var \Illuminate\Database\Eloquent\SoftDeletes $record */
        $record = $this->model->newQuery()->withTrashed()->findOrFail($id);

        return (bool) $record->restore();
    }

    /** Check if a record exists by primary key. */
    public function exists(int|string $id): bool
    {
        return $this->applyTenantScope($this->model->newQuery())
            ->where($this->model->getKeyName(), $id)
            ->exists();
    }

    /** Count records, optionally filtered. */
    public function count(array $filters = []): int
    {
        $query = $this->applyTenantScope($this->model->newQuery());
        $query = $this->applyFilters($query, $filters);

        return $query->count();
    }

    // -------------------------------------------------------------------------
    // Conditional pagination
    // -------------------------------------------------------------------------

    /**
     * Conditionally paginate a source.
     *
     * - When `per_page` is absent: returns a Collection (all rows).
     * - When `per_page` is present: returns a LengthAwarePaginator.
     *
     * Works with Builder instances, Collections, arrays, and any iterable.
     */
    public function conditionalPaginate(mixed $source, array $params = []): mixed
    {
        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : null;
        $page    = isset($params['page']) ? (int) $params['page'] : 1;

        if ($perPage === null) {
            return $this->resolveAll($source);
        }

        return $this->paginateSource($source, $perPage, $page);
    }

    // -------------------------------------------------------------------------
    // Query helpers
    // -------------------------------------------------------------------------

    /** Cache of which models have a tenant_id column (keyed by table name). */
    private static array $tenantColumnCache = [];

    /**
     * Apply tenant scoping so each tenant only sees its own data.
     * Override in child repositories when the model has a tenant_id column.
     */
    protected function applyTenantScope(Builder $query): Builder
    {
        $tenantId = app('tenant.manager')->getCurrentTenantId();

        if ($tenantId !== null && $this->modelHasTenantColumn()) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    /**
     * Check (with in-process caching) whether the model's table has a tenant_id column.
     */
    private function modelHasTenantColumn(): bool
    {
        $table = $this->model->getTable();

        if (!array_key_exists($table, self::$tenantColumnCache)) {
            self::$tenantColumnCache[$table] = $this->model
                ->getConnection()
                ->getSchemaBuilder()
                ->hasColumn($table, 'tenant_id');
        }

        return self::$tenantColumnCache[$table];
    }

    /**
     * Apply column-level equality filters from the $filters array.
     * Only columns listed in $this->filterable are processed.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($this->filterable as $column) {
            if (array_key_exists($column, $filters) && $filters[$column] !== null) {
                $value = $filters[$column];

                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
        }

        // Support date range filters via `from` / `to` on `created_at`.
        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query;
    }

    /**
     * Apply full-text search across all columns listed in $this->searchable.
     */
    protected function applySearch(Builder $query, array $filters): Builder
    {
        $term = $filters['search'] ?? $filters['q'] ?? null;

        if ($term === null || trim((string) $term) === '') {
            return $query;
        }

        $term = '%' . addcslashes(trim((string) $term), '%_') . '%';

        $query->where(function (Builder $q) use ($term): void {
            foreach ($this->searchable as $column) {
                $q->orWhere($column, 'like', $term);
            }
        });

        return $query;
    }

    /**
     * Apply ordering from filters or fall back to the repository default.
     */
    protected function applySorting(Builder $query, array $filters): Builder
    {
        $column    = $filters['sort_by'] ?? $filters['sort'] ?? $this->defaultSortColumn;
        $direction = $filters['sort_dir'] ?? $filters['order'] ?? $this->defaultSortDirection;

        // Sanitise direction to prevent SQL injection.
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        // Sanitise column: allow only word characters and dots.
        if (!preg_match('/^[\w.]+$/', (string) $column)) {
            $column = $this->defaultSortColumn;
        }

        $query->orderBy($column, $direction);

        return $query;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function resolveAll(mixed $source): Collection
    {
        if ($source instanceof Builder) {
            return $source->get();
        }

        if ($source instanceof Collection) {
            return $source;
        }

        if (is_array($source)) {
            return collect($source);
        }

        if (is_iterable($source)) {
            return collect($source);
        }

        return collect([$source]);
    }

    private function paginateSource(mixed $source, int $perPage, int $page): LengthAwarePaginator
    {
        if ($source instanceof Builder) {
            return $source->paginate($perPage, ['*'], 'page', $page);
        }

        $items = match (true) {
            $source instanceof Collection => $source,
            is_array($source)            => collect($source),
            is_iterable($source)         => collect($source),
            default                      => collect([$source]),
        };

        $total  = $items->count();
        $sliced = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $sliced,
            $total,
            $perPage,
            $page,
            [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}
