<?php

namespace App\Core\Repositories;

use App\Core\Interfaces\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Base Repository Implementation
 *
 * Implements the Repository pattern for data access abstraction.
 * Provides consistent CRUD operations with automatic tenant scoping.
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    /**
     * BaseRepository constructor.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->select($columns)->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->select($columns)->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->select($columns)->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes): Model
    {
        return $this->model->create($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $attributes): bool
    {
        $model = $this->findOrFail($id);

        return $model->update($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);

        return $model->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection
    {
        $query = $this->model->select($columns);

        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findWhereFirst(array $criteria, array $columns = ['*']): ?Model
    {
        $query = $this->model->select($columns);

        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    /**
     * Advanced query with filtering, sorting, includes, and pagination.
     *
     * This method leverages Spatie Query Builder to provide:
     * - Field-level and global search/filtering
     * - Multi-field sorting
     * - Sparse field selection
     * - Configurable eager loading of relations
     *
     * @param  array  $config  Configuration array with:
     *                         - 'allowedFilters' => array of allowed filters (strings or AllowedFilter instances)
     *                         - 'allowedSorts' => array of allowed sorts (strings or AllowedSort instances)
     *                         - 'allowedIncludes' => array of allowed includes (strings or AllowedInclude instances)
     *                         - 'allowedFields' => array of allowed fields for sparse fieldsets
     *                         - 'defaultSort' => string default sort field (e.g., '-created_at')
     *                         - 'perPage' => int default items per page (default: 15)
     *                         - 'globalSearch' => array of fields to search globally
     */
    public function queryWithFilters(array $config = []): QueryBuilder
    {
        $query = QueryBuilder::for($this->model);

        // Apply allowed filters
        if (! empty($config['allowedFilters'])) {
            $query->allowedFilters($config['allowedFilters']);
        }

        // Apply allowed sorts
        if (! empty($config['allowedSorts'])) {
            $query->allowedSorts($config['allowedSorts']);
        }

        // Apply allowed includes
        if (! empty($config['allowedIncludes'])) {
            $query->allowedIncludes($config['allowedIncludes']);
        }

        // Apply allowed fields for sparse fieldsets
        if (! empty($config['allowedFields'])) {
            $query->allowedFields($config['allowedFields']);
        }

        // Apply default sort
        if (! empty($config['defaultSort'])) {
            $query->defaultSort($config['defaultSort']);
        }

        // Apply global search if configured
        if (! empty($config['globalSearch']) && request()->has('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($config, $searchTerm) {
                foreach ($config['globalSearch'] as $field) {
                    $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                }
            });
        }

        return $query;
    }

    /**
     * Get paginated results with advanced query capabilities.
     *
     * @param  array  $config  Query configuration
     */
    public function paginateWithFilters(array $config = []): LengthAwarePaginator
    {
        $perPage = $config['perPage'] ?? request('per_page', 15);
        $query = $this->queryWithFilters($config);

        return $query->paginate($perPage)->appends(request()->query());
    }

    /**
     * Get all results with advanced query capabilities.
     *
     * @param  array  $config  Query configuration
     */
    public function getAllWithFilters(array $config = []): Collection
    {
        return $this->queryWithFilters($config)->get();
    }

    /**
     * Get the model instance.
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Set the model instance.
     *
     * @return $this
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }
}
