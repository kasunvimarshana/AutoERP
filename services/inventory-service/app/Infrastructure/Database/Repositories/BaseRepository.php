<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Repositories;

use App\Domain\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as ManualPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Base Repository
 *
 * Fully dynamic, reusable, and customizable base repository.
 * Handles CRUD operations, conditional pagination, filtering, searching,
 * sorting, and cross-service data access.
 *
 * Extend this class for any entity without modifying core logic.
 *
 * Conditional Pagination:
 *   - Returns LengthAwarePaginator when 'per_page' key is present in $filters
 *   - Returns Collection otherwise
 *
 * Supports both 'page' and 'per_page' parameters.
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * Searchable columns for this repository.
     * Override in subclasses to define which columns are searched.
     *
     * @var array<string>
     */
    protected array $searchable = [];

    /**
     * Filterable columns for this repository.
     * Override to specify which columns can be filtered directly.
     *
     * @var array<string>
     */
    protected array $filterable = [];

    /**
     * Default sort column.
     */
    protected string $defaultSortBy = 'created_at';

    /**
     * Default sort direction.
     */
    protected string $defaultSortDir = 'desc';

    public function __construct(
        protected readonly Model $model
    ) {}

    /**
     * {@inheritDoc}
     */
    public function findById(int|string $id, array $relations = []): ?Model
    {
        return $this->model
            ->with($relations)
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(array $criteria, array $relations = []): ?Model
    {
        return $this->model
            ->with($relations)
            ->where($criteria)
            ->first();
    }

    /**
     * {@inheritDoc}
     *
     * Supports the following filter keys:
     *   - search: full-text search across $searchable columns
     *   - sort_by: column to sort by (default: created_at)
     *   - sort_dir: asc|desc (default: desc)
     *   - per_page: items per page (triggers pagination)
     *   - page: current page number (default: 1)
     *   - Any key in $filterable: direct column filter
     */
    public function all(array $filters = [], array $relations = []): Collection|LengthAwarePaginator
    {
        $query = $this->model->with($relations);

        $query = $this->applySearch($query, $filters);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySort($query, $filters);

        // Conditional pagination: paginate when per_page is specified
        if (isset($filters['per_page'])) {
            return $query->paginate(
                perPage: (int) $filters['per_page'],
                columns: ['*'],
                pageName: 'page',
                page: (int) ($filters['page'] ?? 1)
            );
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        return DB::transaction(fn () => $this->model->create($data));
    }

    /**
     * {@inheritDoc}
     */
    public function update(int|string $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data) {
            $record = $this->model->findOrFail($id);
            $record->update($data);
            return $record->fresh();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int|string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $record = $this->model->find($id);
            return $record ? (bool) $record->delete() : false;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function exists(array $criteria): bool
    {
        return $this->model->where($criteria)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $criteria = []): int
    {
        return empty($criteria)
            ? $this->model->count()
            : $this->model->where($criteria)->count();
    }

    /**
     * {@inheritDoc}
     *
     * Paginates any iterable data source (arrays, collections, API responses).
     * This enables consistent pagination format across heterogeneous data sources
     * including cross-service API calls.
     */
    public function paginateIterable(iterable $data, int $perPage, int $page = 1): LengthAwarePaginator
    {
        // Normalize to array
        $items = match (true) {
            $data instanceof Collection => $data->values(),
            is_array($data) => collect($data),
            default => collect(iterator_to_array($data)),
        };

        $total = $items->count();
        $offset = ($page - 1) * $perPage;

        $pageItems = $items->slice($offset, $perPage)->values();

        return new ManualPaginator(
            items: $pageItems,
            total: $total,
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function bulkCreate(array $records): bool
    {
        return DB::transaction(fn () => $this->model->insert($records));
    }

    /**
     * {@inheritDoc}
     */
    public function findMany(array $ids, array $relations = []): Collection
    {
        return $this->model
            ->with($relations)
            ->whereIn($this->model->getKeyName(), $ids)
            ->get();
    }

    /**
     * Get a new query builder instance for the model.
     * Available to subclasses for custom queries.
     */
    protected function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Apply full-text search across searchable columns.
     */
    protected function applySearch(Builder $query, array $filters): Builder
    {
        if (!isset($filters['search']) || empty($this->searchable)) {
            return $query;
        }

        $search = $filters['search'];

        return $query->where(function (Builder $q) use ($search) {
            foreach ($this->searchable as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Apply column-level filters from $filterable whitelist.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($this->filterable as $column) {
            if (array_key_exists($column, $filters) && $filters[$column] !== null) {
                if (is_array($filters[$column])) {
                    $query->whereIn($column, $filters[$column]);
                } else {
                    $query->where($column, $filters[$column]);
                }
            }
        }

        return $query;
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySort(Builder $query, array $filters): Builder
    {
        $sortBy = $filters['sort_by'] ?? $this->defaultSortBy;
        $sortDir = $filters['sort_dir'] ?? $this->defaultSortDir;

        // Whitelist sort columns to prevent injection
        $allowedColumns = array_merge(
            $this->searchable,
            $this->filterable,
            [$this->defaultSortBy, 'id', 'updated_at']
        );

        if (in_array($sortBy, $allowedColumns, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query;
    }
}
