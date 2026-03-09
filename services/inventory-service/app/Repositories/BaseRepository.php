<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * BaseRepository — identical pattern to auth-service BaseRepository.
 *
 * Features:
 *  - Pipeline query builder
 *  - Conditional pagination (per_page → paginate else get all)
 *  - Filtering with 10 operators (=, !=, >, >=, <, <=, like, not_like, in, not_in)
 *  - Search (ILIKE across configured fields)
 *  - Sorting
 *  - Eager loading
 *  - Works with Eloquent/collections/arrays
 */
abstract class BaseRepository
{
    protected Model $model;

    /** Fields that support full-text ILIKE search. Override in subclasses. */
    protected array $searchableFields = [];

    /** Default eager-load relations. Override in subclasses. */
    protected array $with = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    // -------------------------------------------------------------------------
    // Core CRUD
    // -------------------------------------------------------------------------

    public function find(string $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail(string $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(string $id): bool
    {
        $record = $this->findOrFail($id);
        return (bool) $record->delete();
    }

    // -------------------------------------------------------------------------
    // Pipeline Query Builder
    // -------------------------------------------------------------------------

    /**
     * Build a query from a parameter array and return paginated or full results.
     *
     * Supported params:
     *   - filter[field][operator]=value  (or filter[field]=value for equality)
     *   - search=term                    (ILIKE across $searchableFields)
     *   - sort=field or sort=-field      (prefix – for descending)
     *   - with=relation1,relation2
     *   - per_page=N                     (triggers pagination)
     *   - page=N                         (used with per_page)
     *   - fields=col1,col2               (select specific columns)
     */
    public function query(array $params = []): mixed
    {
        $query = $this->model->newQuery();

        $query = $this->applyEagerLoads($query, $params);
        $query = $this->applyFilters($query, $params);
        $query = $this->applySearch($query, $params);
        $query = $this->applyFieldSelection($query, $params);
        $query = $this->applySorting($query, $params);

        return $this->applyPagination($query, $params);
    }

    // -------------------------------------------------------------------------
    // Eager Loading
    // -------------------------------------------------------------------------

    protected function applyEagerLoads(Builder $query, array $params): Builder
    {
        $defaultRelations = $this->with;

        if (!empty($params['with'])) {
            $requested = is_array($params['with'])
                ? $params['with']
                : explode(',', $params['with']);

            $allowed   = $this->getAllowedRelations();
            $relations = array_filter($requested, fn ($r) => in_array(trim($r), $allowed, true));

            $defaultRelations = array_unique(array_merge($defaultRelations, $relations));
        }

        if (!empty($defaultRelations)) {
            $query->with($defaultRelations);
        }

        return $query;
    }

    /**
     * Override in subclasses to restrict which relations can be eager-loaded via ?with=
     */
    protected function getAllowedRelations(): array
    {
        return [];
    }

    // -------------------------------------------------------------------------
    // Filtering (10 operators)
    // -------------------------------------------------------------------------

    protected function applyFilters(Builder $query, array $params): Builder
    {
        if (empty($params['filter']) || !is_array($params['filter'])) {
            return $query;
        }

        foreach ($params['filter'] as $field => $value) {
            if (!$this->isAllowedFilterField($field)) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $operator => $operand) {
                    $query = $this->applyOperator($query, $field, $operator, $operand);
                }
            } else {
                $query->where($field, '=', $value);
            }
        }

        return $query;
    }

    private function applyOperator(Builder $query, string $field, string $operator, mixed $value): Builder
    {
        return match ($operator) {
            'eq'       => $query->where($field, '=', $value),
            'neq'      => $query->where($field, '!=', $value),
            'gt'       => $query->where($field, '>', $value),
            'gte'      => $query->where($field, '>=', $value),
            'lt'       => $query->where($field, '<', $value),
            'lte'      => $query->where($field, '<=', $value),
            'like'     => $query->where($field, 'ILIKE', "%{$value}%"),
            'not_like' => $query->where($field, 'NOT ILIKE', "%{$value}%"),
            'in'       => $query->whereIn($field, is_array($value) ? $value : explode(',', $value)),
            'not_in'   => $query->whereNotIn($field, is_array($value) ? $value : explode(',', $value)),
            'null'     => $query->whereNull($field),
            'not_null' => $query->whereNotNull($field),
            default    => $query, // Ignore unknown operators
        };
    }

    /**
     * Override in subclasses to restrict filterable fields.
     * Return empty array to allow all fields (use carefully).
     */
    protected function getAllowedFilterFields(): array
    {
        return [];
    }

    private function isAllowedFilterField(string $field): bool
    {
        $allowed = $this->getAllowedFilterFields();
        return empty($allowed) || in_array($field, $allowed, true);
    }

    // -------------------------------------------------------------------------
    // Search (ILIKE)
    // -------------------------------------------------------------------------

    protected function applySearch(Builder $query, array $params): Builder
    {
        $term = $params['search'] ?? $params['q'] ?? null;

        if (empty($term) || empty($this->searchableFields)) {
            return $query;
        }

        $term = '%' . trim($term) . '%';

        $query->where(function (Builder $q) use ($term) {
            foreach ($this->searchableFields as $field) {
                $q->orWhere($field, 'ILIKE', $term);
            }
        });

        return $query;
    }

    // -------------------------------------------------------------------------
    // Field Selection
    // -------------------------------------------------------------------------

    protected function applyFieldSelection(Builder $query, array $params): Builder
    {
        if (!empty($params['fields'])) {
            $fields = is_array($params['fields'])
                ? $params['fields']
                : explode(',', $params['fields']);

            $allowed = $this->getAllowedSelectFields();

            if (!empty($allowed)) {
                $fields = array_intersect($fields, $allowed);
            }

            if (!empty($fields)) {
                $query->select(array_map('trim', $fields));
            }
        }

        return $query;
    }

    protected function getAllowedSelectFields(): array
    {
        return [];
    }

    // -------------------------------------------------------------------------
    // Sorting
    // -------------------------------------------------------------------------

    protected function applySorting(Builder $query, array $params): Builder
    {
        $sort = $params['sort'] ?? $params['order_by'] ?? null;

        if (empty($sort)) {
            return $this->applyDefaultSort($query);
        }

        $sorts = is_array($sort) ? $sort : explode(',', $sort);

        foreach ($sorts as $sortItem) {
            $sortItem   = trim($sortItem);
            $direction  = 'asc';

            if (str_starts_with($sortItem, '-')) {
                $direction = 'desc';
                $sortItem  = substr($sortItem, 1);
            }

            if ($this->isAllowedSortField($sortItem)) {
                $query->orderBy($sortItem, $direction);
            }
        }

        return $query;
    }

    protected function applyDefaultSort(Builder $query): Builder
    {
        return $query->latest();
    }

    protected function getAllowedSortFields(): array
    {
        return ['created_at', 'updated_at', 'id'];
    }

    private function isAllowedSortField(string $field): bool
    {
        $allowed = $this->getAllowedSortFields();
        return in_array($field, $allowed, true);
    }

    // -------------------------------------------------------------------------
    // Pagination
    // -------------------------------------------------------------------------

    protected function applyPagination(Builder $query, array $params): mixed
    {
        $perPage = $params['per_page'] ?? null;

        if ($perPage !== null) {
            $maxPerPage = (int) config('inventory.pagination.max_per_page', 100);
            $perPage    = min((int) $perPage, $maxPerPage);
            $perPage    = max(1, $perPage);

            return $query->paginate($perPage, ['*'], 'page', $params['page'] ?? 1);
        }

        return $query->get();
    }
}
