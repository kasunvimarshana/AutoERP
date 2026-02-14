<?php

namespace App\Core;

use App\Core\Traits\DynamicQueryBuilder;
use App\DTOs\QueryConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface BaseRepositoryInterface
 * 
 * @package App\Core
 */
interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']): Collection;
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;
    public function find(int $id, array $columns = ['*']): ?Model;
    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model;
    public function findWhere(array $criteria, array $columns = ['*']): Collection;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function with(array $relations): self;
    public function query(QueryConfig $config);
}

/**
 * Class BaseRepository
 * 
 * Base repository implementation providing common data access patterns
 * with advanced dynamic query capabilities
 * 
 * @package App\Core
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    use DynamicQueryBuilder;

    /**
     * @var Model
     */
    protected Model $model;

    /**
     * @var array
     */
    protected array $with = [];

    /**
     * Allowed fields for filtering, sorting, and searching
     * Override in child classes to restrict queryable fields
     * 
     * @var array
     */
    protected array $allowedFields = [];

    /**
     * Allowed relations for eager loading
     * Override in child classes to restrict loadable relations
     * 
     * @var array
     */
    protected array $allowedRelations = [];

    /**
     * Default searchable fields for global search
     * Override in child classes to define searchable fields
     * 
     * @var array
     */
    protected array $searchableFields = [];

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->with($this->with)->get($columns);
    }

    /**
     * Paginate records
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->with($this->with)->paginate($perPage, $columns);
    }

    /**
     * Find record by ID
     *
     * @param int $id
     * @param array $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->with($this->with)->find($id, $columns);
    }

    /**
     * Find record by field
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return Model|null
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->model->with($this->with)->where($field, '=', $value)->first($columns);
    }

    /**
     * Find records by criteria
     *
     * @param array $criteria
     * @param array $columns
     * @return Collection
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection
    {
        $query = $this->model->with($this->with);
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                [$operator, $val] = $value;
                $query->where($field, $operator, $val);
            } else {
                $query->where($field, '=', $value);
            }
        }
        
        return $query->get($columns);
    }

    /**
     * Create new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update record
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->find($id);
        
        if (!$record) {
            return false;
        }
        
        return $record->update($data);
    }

    /**
     * Delete record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $record = $this->find($id);
        
        if (!$record) {
            return false;
        }
        
        return $record->delete();
    }

    /**
     * Set relationships to eager load
     *
     * @param array $relations
     * @return self
     */
    public function with(array $relations): self
    {
        $this->with = $relations;
        return $this;
    }

    /**
     * Reset the repository state
     *
     * @return void
     */
    protected function reset(): void
    {
        $this->with = [];
    }

    /**
     * Dynamic query using QueryConfig
     * 
     * This is the main method for executing configuration-driven queries
     * with support for filtering, searching, sorting, pagination, and eager loading
     *
     * @param QueryConfig $config
     * @return LengthAwarePaginator|Collection
     */
    public function query(QueryConfig $config)
    {
        // Validate config if allowed fields/relations are defined
        if (!empty($this->allowedFields) || !empty($this->allowedRelations)) {
            $errors = $config->validate($this->allowedFields, $this->allowedRelations);
            if (!empty($errors)) {
                throw new \InvalidArgumentException('Invalid query config: ' . implode(', ', $errors));
            }
        }

        // Use searchable fields if search is requested but no fields specified
        if ($config->search && empty($config->search['fields']) && !empty($this->searchableFields)) {
            $config->search['fields'] = $this->searchableFields;
        }

        // Build and execute dynamic query
        return $this->buildDynamicQuery($this->model->newQuery(), $config);
    }

    /**
     * Get searchable fields
     *
     * @return array
     */
    public function getSearchableFields(): array
    {
        return $this->searchableFields;
    }

    /**
     * Get allowed fields
     *
     * @return array
     */
    public function getAllowedFields(): array
    {
        return $this->allowedFields;
    }

    /**
     * Get allowed relations
     *
     * @return array
     */
    public function getAllowedRelations(): array
    {
        return $this->allowedRelations;
    }
}
