<?php

namespace App\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository
 * 
 * Provides common data access methods for all repositories.
 * Note: Methods like restore() and forceDelete() assume the model uses SoftDeletes trait.
 * If your model doesn't use soft deletes, don't call these methods.
 */
abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    public function find(string $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->find($id, $columns);
    }

    public function findBy(string $field, $value, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->where($field, $value)->first($columns);
    }

    public function findWhere(array $conditions, array $columns = ['*'], array $relations = []): Collection
    {
        $query = $this->model->with($relations);
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->get($columns);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): bool
    {
        $record = $this->find($id);
        
        if (!$record) {
            return false;
        }
        
        return $record->update($data);
    }

    public function delete(string $id): bool
    {
        $record = $this->find($id);
        
        if (!$record) {
            return false;
        }
        
        return $record->delete();
    }

    /**
     * Restore a soft-deleted record
     * Note: Only works if model uses SoftDeletes trait
     */
    public function restore(string $id): bool
    {
        $record = $this->model->withTrashed()->find($id);
        
        if (!$record) {
            return false;
        }
        
        return $record->restore();
    }

    /**
     * Permanently delete a record
     * Note: Only works if model uses SoftDeletes trait
     */
    public function forceDelete(string $id): bool
    {
        $record = $this->model->withTrashed()->find($id);
        
        if (!$record) {
            return false;
        }
        
        return $record->forceDelete();
    }

    public function exists(string $field, $value): bool
    {
        return $this->model->where($field, $value)->exists();
    }

    public function count(array $conditions = []): int
    {
        $query = $this->model->newQuery();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->count();
    }
}
