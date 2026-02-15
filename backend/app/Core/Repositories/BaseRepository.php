<?php

namespace App\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

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
     * @param array $relations
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    /**
     * Get paginated records
     *
     * @param int $perPage
     * @param array $columns
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    /**
     * Find a record by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @return Model|null
     */
    public function findById(int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->find($id, $columns);
    }

    /**
     * Find records by criteria
     *
     * @param array $criteria
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function findBy(array $criteria, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->buildQuery($criteria)->with($relations)->get($columns);
    }

    /**
     * Find single record by criteria
     *
     * @param array $criteria
     * @param array $columns
     * @param array $relations
     * @return Model|null
     */
    public function findOneBy(array $criteria, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->buildQuery($criteria)->with($relations)->first($columns);
    }

    /**
     * Create a new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->findById($id);
        
        if (!$record) {
            return false;
        }
        
        return $record->update($data);
    }

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $record = $this->findById($id);
        
        if (!$record) {
            return false;
        }
        
        return $record->delete();
    }

    /**
     * Bulk create records
     *
     * @param array $data
     * @return bool
     */
    public function bulkCreate(array $data): bool
    {
        return $this->model->insert($data);
    }

    /**
     * Bulk update records
     *
     * @param array $data
     * @return bool
     */
    public function bulkUpdate(array $data): bool
    {
        // Implementation depends on specific use case
        // This is a basic example
        foreach ($data as $item) {
            if (isset($item['id'])) {
                $this->update($item['id'], $item);
            }
        }
        
        return true;
    }

    /**
     * Count records by criteria
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int
    {
        return $this->buildQuery($criteria)->count();
    }

    /**
     * Check if record exists
     *
     * @param array $criteria
     * @return bool
     */
    public function exists(array $criteria): bool
    {
        return $this->buildQuery($criteria)->exists();
    }

    /**
     * Build query from criteria
     *
     * @param array $criteria
     * @return Builder
     */
    protected function buildQuery(array $criteria): Builder
    {
        $query = $this->model->query();
        
        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }
        
        return $query;
    }
}
