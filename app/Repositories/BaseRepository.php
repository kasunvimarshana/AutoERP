<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Base Repository Implementation
 * 
 * Provides common data access functionality for all repositories.
 * Implements advanced query capabilities including filtering, searching,
 * sorting, pagination, and eager loading with tenant awareness.
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The model instance
     */
    protected Model $model;

    /**
     * The query builder instance
     */
    protected Builder $query;

    /**
     * Configuration for the current query
     */
    protected array $config = [];

    /**
     * Constructor
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->resetQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $config = []): Collection
    {
        $this->applyConfig($config);
        
        return $this->query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15, array $config = []): LengthAwarePaginator
    {
        $this->applyConfig($config);
        
        return $this->query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function find(int|string $id, array $relations = [], array $columns = ['*']): ?Model
    {
        $query = $this->model->newQuery();
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->select($columns)->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int|string $id, array $relations = [], array $columns = ['*']): Model
    {
        $query = $this->model->newQuery();
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->select($columns)->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(string $field, mixed $value, array $config = []): Collection
    {
        $this->resetQuery();
        $this->query->where($field, $value);
        $this->applyConfig($config);
        
        return $this->query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findFirstBy(string $field, mixed $value, array $relations = [], array $columns = ['*']): ?Model
    {
        $query = $this->model->newQuery();
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->select($columns)->where($field, $value)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(int|string $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);
        
        return $model->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int|string $id): bool
    {
        $model = $this->findOrFail($id);
        
        return $model->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function bulkDelete(array $ids): int
    {
        return $this->model->newQuery()->whereIn('id', $ids)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $filters = []): int
    {
        $query = $this->model->newQuery();
        
        if (!empty($filters)) {
            $this->applyFilters($query, $filters);
        }
        
        return $query->count();
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $field, mixed $value, int|string|null $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where($field, $value);
        
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function applyConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        
        // Apply sparse field selection
        if (isset($config['columns']) && !empty($config['columns'])) {
            $this->query->select($config['columns']);
        }
        
        // Apply eager loading
        if (isset($config['relations']) && !empty($config['relations'])) {
            $this->applyEagerLoading($config['relations']);
        }
        
        // Apply filters
        if (isset($config['filters']) && !empty($config['filters'])) {
            $this->applyFilters($this->query, $config['filters']);
        }
        
        // Apply search
        if (isset($config['search']) && !empty($config['search'])) {
            $this->applySearch($config['search']);
        }
        
        // Apply sorting
        if (isset($config['sorts']) && !empty($config['sorts'])) {
            $this->applySorts($config['sorts']);
        }
        
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resetQuery(): self
    {
        $this->query = $this->model->newQuery();
        $this->config = [];
        
        return $this;
    }

    /**
     * Apply eager loading with optional field selection
     *
     * @param array $relations
     * @return void
     */
    protected function applyEagerLoading(array $relations): void
    {
        foreach ($relations as $key => $value) {
            if (is_string($key) && is_array($value)) {
                // Relation with field selection: ['posts' => ['id', 'title']]
                $this->query->with([$key => function ($query) use ($value) {
                    $query->select($value);
                }]);
            } elseif (is_string($value)) {
                // Simple relation: ['posts', 'comments']
                $this->query->with($value);
            }
        }
    }

    /**
     * Apply filters to query
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $field => $condition) {
            if (is_array($condition)) {
                // Complex filter: ['status' => ['in' => ['active', 'pending']]]
                $this->applyComplexFilter($query, $field, $condition);
            } else {
                // Simple equality filter: ['status' => 'active']
                $query->where($field, $condition);
            }
        }
    }

    /**
     * Apply complex filter conditions
     *
     * @param Builder $query
     * @param string $field
     * @param array $condition
     * @return void
     */
    protected function applyComplexFilter(Builder $query, string $field, array $condition): void
    {
        foreach ($condition as $operator => $value) {
            match ($operator) {
                'eq' => $query->where($field, '=', $value),
                'ne', 'neq' => $query->where($field, '!=', $value),
                'gt' => $query->where($field, '>', $value),
                'gte' => $query->where($field, '>=', $value),
                'lt' => $query->where($field, '<', $value),
                'lte' => $query->where($field, '<=', $value),
                'like' => $query->where($field, 'like', "%{$value}%"),
                'not_like' => $query->where($field, 'not like', "%{$value}%"),
                'in' => $query->whereIn($field, (array) $value),
                'not_in' => $query->whereNotIn($field, (array) $value),
                'null' => $value ? $query->whereNull($field) : $query->whereNotNull($field),
                'between' => $query->whereBetween($field, $value),
                'not_between' => $query->whereNotBetween($field, $value),
                default => null
            };
        }
    }

    /**
     * Apply global or field-specific search
     *
     * @param array $search
     * @return void
     */
    protected function applySearch(array $search): void
    {
        if (isset($search['query']) && isset($search['fields'])) {
            $searchQuery = $search['query'];
            $searchFields = (array) $search['fields'];
            
            $this->query->where(function ($query) use ($searchQuery, $searchFields) {
                foreach ($searchFields as $field) {
                    $query->orWhere($field, 'like', "%{$searchQuery}%");
                }
            });
        }
    }

    /**
     * Apply multi-field sorting
     *
     * @param array $sorts
     * @return void
     */
    protected function applySorts(array $sorts): void
    {
        foreach ($sorts as $field => $direction) {
            if (is_numeric($field)) {
                // Array format: ['created_at', 'desc']
                $field = $direction;
                $direction = 'asc';
            }
            
            $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
            $this->query->orderBy($field, $direction);
        }
    }

    /**
     * Get the model instance
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Get the query builder instance
     *
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }
}
