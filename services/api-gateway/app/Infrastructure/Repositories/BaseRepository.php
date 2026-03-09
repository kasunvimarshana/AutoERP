<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Contracts\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as ManualPaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Base Repository
 *
 * Fully dynamic, reusable base repository that handles CRUD operations,
 * conditional pagination, filtering, searching, sorting, and cross-service
 * data access. Can be extended for any entity without modifying core logic.
 *
 * Pagination behavior:
 * - Returns paginated results when 'per_page' exists in params
 * - Returns all results otherwise
 * - Supports both 'page' and 'per_page' parameters
 * - Works with Eloquent queries, arrays, collections, and API responses
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * The Eloquent model instance.
     */
    protected Model $model;

    /**
     * Default columns for search operations.
     *
     * @var array<string>
     */
    protected array $searchableColumns = [];

    /**
     * Default sortable columns.
     *
     * @var array<string>
     */
    protected array $sortableColumns = ['created_at', 'updated_at'];

    /**
     * Allowed filter columns.
     *
     * @var array<string>
     */
    protected array $filterableColumns = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string|int $id, array $relations = []): ?Model
    {
        return $this->model
            ->newQuery()
            ->with($relations)
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(string $field, mixed $value, array $relations = []): ?Model
    {
        return $this->model
            ->newQuery()
            ->with($relations)
            ->where($field, $value)
            ->first();
    }

    /**
     * {@inheritdoc}
     *
     * Conditional pagination: returns paginated when 'per_page' param exists,
     * all results otherwise.
     */
    public function getAll(array $params = []): Collection|LengthAwarePaginator
    {
        $query = $this->buildQuery($params);

        if (isset($params['per_page'])) {
            $perPage = (int) $params['per_page'];
            $page = (int) ($params['page'] ?? 1);

            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string|int $id, array $data): Model
    {
        $record = $this->findById($id);

        if (!$record) {
            throw new \RuntimeException(
                sprintf('Record of type %s with ID %s not found.', get_class($this->model), $id)
            );
        }

        $record->update($data);

        return $record->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string|int $id): bool
    {
        $record = $this->findById($id);

        if (!$record) {
            return false;
        }

        return (bool) $record->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function restore(string|int $id): bool
    {
        $record = $this->model
            ->newQuery()
            ->withTrashed()
            ->find($id);

        if (!$record) {
            return false;
        }

        return (bool) $record->restore();
    }

    /**
     * {@inheritdoc}
     *
     * Supports pagination for arrays, collections, API responses, and any iterable.
     * Returns paginated results when 'per_page' exists in params.
     */
    public function paginateData(mixed $data, array $params = []): array|LengthAwarePaginator
    {
        // Convert to collection if needed
        $collection = match (true) {
            $data instanceof SupportCollection => $data,
            is_array($data) => collect($data),
            $data instanceof \Traversable => collect(iterator_to_array($data)),
            default => collect((array) $data),
        };

        if (!isset($params['per_page'])) {
            return $collection->all();
        }

        $perPage = (int) $params['per_page'];
        $page = (int) ($params['page'] ?? 1);
        $total = $collection->count();
        $items = $collection->forPage($page, $perPage)->values();

        return new ManualPaginator(
            items: $items->all(),
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
     * Build an Eloquent query with filtering, searching, and sorting applied.
     *
     * @param  array<string, mixed>  $params
     */
    protected function buildQuery(array $params = []): Builder
    {
        $query = $this->model->newQuery();

        // Apply tenant isolation if context is set
        $this->applyTenantScope($query, $params);

        // Apply search
        if (!empty($params['search']) && !empty($this->searchableColumns)) {
            $search = $params['search'];
            $query->where(function (Builder $q) use ($search) {
                foreach ($this->searchableColumns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$search}%");
                }
            });
        }

        // Apply filters
        if (!empty($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $column => $value) {
                if (in_array($column, $this->filterableColumns, true)) {
                    if (is_array($value)) {
                        $query->whereIn($column, $value);
                    } else {
                        $query->where($column, $value);
                    }
                }
            }
        }

        // Apply direct column filters from params
        foreach ($this->filterableColumns as $column) {
            if (isset($params[$column]) && $params[$column] !== '') {
                $query->where($column, $params[$column]);
            }
        }

        // Apply sorting
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDir = strtolower($params['sort_dir'] ?? 'desc');

        if (in_array($sortBy, $this->sortableColumns, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Allow subclasses to customize the query further
        return $this->applyCustomScope($query, $params);
    }

    /**
     * Apply tenant-aware scope to the query.
     * Override in subclasses for custom tenant isolation logic.
     */
    protected function applyTenantScope(Builder $query, array $params): void
    {
        if (isset($params['tenant_id'])) {
            $query->where('tenant_id', $params['tenant_id']);
        }
    }

    /**
     * Hook for subclasses to apply additional query constraints.
     * Override without modifying core logic.
     */
    protected function applyCustomScope(Builder $query, array $params): Builder
    {
        return $query;
    }
}
