<?php

namespace App\Core\Repositories;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Repository Implementation
 * 
 * Provides common data access methods following the Repository pattern.
 * All module-specific repositories should extend this class.
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;
    protected Builder $query;

    public function __construct()
    {
        $this->model = $this->makeModel();
        $this->query = $this->model->newQuery();
    }

    abstract protected function model(): string;

    protected function makeModel(): Model
    {
        $model = app($this->model());

        if (!$model instanceof Model) {
            throw new \RuntimeException(
                "Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model"
            );
        }

        return $model;
    }

    protected function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
    }

    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->query->find($id, $columns);
    }

    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->query->findOrFail($id, $columns);
    }

    public function findBy(string $column, $value, array $columns = ['*']): Collection
    {
        return $this->query->where($column, $value)->get($columns);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->query->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query->paginate($perPage, $columns);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    public function count(array $criteria = []): int
    {
        if (!empty($criteria)) {
            $this->whereCriteria($criteria);
        }
        
        $count = $this->query->count();
        $this->resetQuery();
        
        return $count;
    }

    public function exists(int $id): bool
    {
        return $this->query->where($this->model->getKeyName(), $id)->exists();
    }

    public function with(array $relations, array $columns = ['*']): Collection
    {
        return $this->query->with($relations)->get($columns);
    }

    public function whereCriteria(array $criteria): self
    {
        foreach ($criteria as $column => $value) {
            if (is_array($value)) {
                $this->query->whereIn($column, $value);
            } else {
                $this->query->where($column, $value);
            }
        }

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }
}
