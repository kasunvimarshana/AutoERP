<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    protected Model $model;

    /**
     * Return the Eloquent model class for this repository.
     */
    abstract protected function getModelClass(): string;

    public function __construct()
    {
        $this->model = app($this->getModelClass());
    }

    // -----------------------------------------------------------------------
    // Basic CRUD
    // -----------------------------------------------------------------------

    public function find(string|int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findOrFail(string|int $id): Model
    {
        return $this->newQuery()->findOrFail($id);
    }

    public function create(array $data): Model
    {
        $data = $this->beforeCreate($data);
        $model = $this->model->newInstance();
        $model->fill($data);
        $model->save();
        $this->afterCreate($model);
        return $model->fresh();
    }

    public function update(string|int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $data  = $this->beforeUpdate($model, $data);
        $model->fill($data);
        $model->save();
        $this->afterUpdate($model);
        return $model->fresh();
    }

    public function delete(string|int $id): bool
    {
        $model = $this->findOrFail($id);
        return (bool) $model->delete();
    }

    public function findBy(string $field, mixed $value): ?Model
    {
        return $this->newQuery()->where($field, $value)->first();
    }

    public function findAllBy(string $field, mixed $value, array $params = []): LengthAwarePaginator|Collection
    {
        $query = $this->newQuery()->where($field, $value);
        return $this->applyParamsAndExecute($query, $params);
    }

    // -----------------------------------------------------------------------
    // findAll — the heart of the repository
    // -----------------------------------------------------------------------

    /**
     * Fetch all records with optional:
     *   - pagination:  per_page + page
     *   - filters:     filters[] array with field/operator/value
     *   - search:      search + search_fields[]
     *   - sorting:     sort_by + sort_direction
     *   - eager load:  with[]
     */
    public function findAll(array $params = []): LengthAwarePaginator|Collection
    {
        $query = $this->newQuery();
        return $this->applyParamsAndExecute($query, $params);
    }

    // -----------------------------------------------------------------------
    // Query Building Pipeline
    // -----------------------------------------------------------------------

    protected function applyParamsAndExecute(Builder $query, array $params): LengthAwarePaginator|Collection
    {
        $query = $this->applyEagerLoading($query, $params);
        $query = $this->applyFilters($query, $params);
        $query = $this->applySearch($query, $params);
        $query = $this->applySorting($query, $params);
        $query = $this->applyCustomScopes($query, $params);

        return $this->applyPagination($query, $params);
    }

    /**
     * Eager loading via 'with' param (string or array).
     */
    protected function applyEagerLoading(Builder $query, array $params): Builder
    {
        $with = $params['with'] ?? [];

        if (is_string($with)) {
            $with = explode(',', $with);
        }

        if (!empty($with)) {
            $query->with(array_map('trim', $with));
        }

        return $query;
    }

    /**
     * Apply structured filters.
     *
     * params['filters'] = [
     *   ['field' => 'status', 'operator' => '=',    'value' => 'active'],
     *   ['field' => 'age',    'operator' => '>=',   'value' => 18],
     *   ['field' => 'name',   'operator' => 'like', 'value' => 'John%'],
     *   ['field' => 'id',     'operator' => 'in',   'value' => [1,2,3]],
     *   ['field' => 'price',  'operator' => 'between', 'value' => [10, 100]],
     * ]
     *
     * Alternatively simple key=>value pairs can be passed in params for equality filters.
     */
    protected function applyFilters(Builder $query, array $params): Builder
    {
        // Structured filters array
        if (!empty($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $filter) {
                if (!isset($filter['field'])) {
                    continue;
                }

                $field    = $filter['field'];
                $operator = strtolower($filter['operator'] ?? '=');
                $value    = $filter['value'] ?? null;

                // Only allow whitelisted columns to prevent injection
                if (!$this->isFilterableColumn($field)) {
                    continue;
                }

                switch ($operator) {
                    case '=':
                    case '!=':
                    case '>':
                    case '<':
                    case '>=':
                    case '<=':
                        $query->where($field, $operator, $value);
                        break;

                    case 'like':
                    case 'ilike':
                        $query->where($field, 'ILIKE', $value);
                        break;

                    case 'not like':
                        $query->where($field, 'NOT ILIKE', $value);
                        break;

                    case 'in':
                        if (is_array($value)) {
                            $query->whereIn($field, $value);
                        }
                        break;

                    case 'not in':
                        if (is_array($value)) {
                            $query->whereNotIn($field, $value);
                        }
                        break;

                    case 'between':
                        if (is_array($value) && count($value) === 2) {
                            $query->whereBetween($field, $value);
                        }
                        break;

                    case 'not between':
                        if (is_array($value) && count($value) === 2) {
                            $query->whereNotBetween($field, $value);
                        }
                        break;

                    case 'null':
                        $query->whereNull($field);
                        break;

                    case 'not null':
                        $query->whereNotNull($field);
                        break;

                    case 'date':
                        $query->whereDate($field, $value);
                        break;
                }
            }
        }

        return $query;
    }

    /**
     * Full-text search across multiple columns.
     *
     * params['search']        = 'john doe'
     * params['search_fields'] = ['name', 'email']   (or comma-separated string)
     */
    protected function applySearch(Builder $query, array $params): Builder
    {
        $search = $params['search'] ?? null;
        $fields = $params['search_fields'] ?? $this->getDefaultSearchFields();

        if (empty($search) || empty($fields)) {
            return $query;
        }

        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        $searchTerm = '%' . mb_strtolower(trim($search)) . '%';

        $query->where(function (Builder $q) use ($fields, $searchTerm) {
            foreach ($fields as $field) {
                $field = trim($field);
                if ($this->isFilterableColumn($field)) {
                    $q->orWhereRaw("LOWER({$field}::text) LIKE ?", [$searchTerm]);
                }
            }
        });

        return $query;
    }

    /**
     * Apply sorting.
     *
     * params['sort_by']        = 'created_at'
     * params['sort_direction'] = 'desc'
     */
    protected function applySorting(Builder $query, array $params): Builder
    {
        $sortBy        = $params['sort_by'] ?? $this->getDefaultSortField();
        $sortDirection = strtolower($params['sort_direction'] ?? $this->getDefaultSortDirection());

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        if ($sortBy && $this->isSortableColumn($sortBy)) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif ($this->getDefaultSortField()) {
            $query->orderBy($this->getDefaultSortField(), $this->getDefaultSortDirection());
        }

        return $query;
    }

    /**
     * Apply pagination or return collection.
     *
     * If 'per_page' present in params, paginate.
     * Otherwise return all records as a Collection.
     */
    protected function applyPagination(Builder $query, array $params): LengthAwarePaginator|Collection
    {
        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : null;

        if ($perPage !== null && $perPage > 0) {
            $page = isset($params['page']) ? (int) $params['page'] : 1;
            return $query->paginate(
                perPage: min($perPage, 200), // hard cap to prevent abuse
                page: $page
            );
        }

        return $query->get();
    }

    /**
     * Hook for custom scopes (override in concrete repositories).
     */
    protected function applyCustomScopes(Builder $query, array $params): Builder
    {
        return $query;
    }

    // -----------------------------------------------------------------------
    // Lifecycle Hooks (override in subclasses)
    // -----------------------------------------------------------------------

    protected function beforeCreate(array $data): array
    {
        return $data;
    }

    protected function afterCreate(Model $model): void {}

    protected function beforeUpdate(Model $model, array $data): array
    {
        return $data;
    }

    protected function afterUpdate(Model $model): void {}

    // -----------------------------------------------------------------------
    // Column Whitelisting / Configuration
    // -----------------------------------------------------------------------

    /**
     * Override to specify which columns can be filtered.
     * By default allows all fillable columns.
     */
    protected function getFilterableColumns(): array
    {
        return $this->model->getFillable();
    }

    protected function isFilterableColumn(string $column): bool
    {
        $allowed = $this->getFilterableColumns();
        // Empty means all allowed (no whitelist set)
        if (empty($allowed)) {
            return true;
        }
        // Allow dot notation for JSON columns (e.g. "settings->key")
        $base = explode('->', $column)[0];
        return in_array($base, $allowed, true);
    }

    protected function isSortableColumn(string $column): bool
    {
        return $this->isFilterableColumn($column);
    }

    protected function getDefaultSearchFields(): array
    {
        return [];
    }

    protected function getDefaultSortField(): ?string
    {
        return 'created_at';
    }

    protected function getDefaultSortDirection(): string
    {
        return 'desc';
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    protected function newQuery(): Builder
    {
        return $this->model->newQuery();
    }
}
