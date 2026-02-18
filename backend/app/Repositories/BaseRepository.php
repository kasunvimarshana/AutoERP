<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The model instance.
     */
    protected Model $model;

    /**
     * Create a new repository instance.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records.
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->get($columns);
    }

    /**
     * Get paginated records.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage, $columns);
    }

    /**
     * Find a record by ID.
     */
    public function find(string|int $id, array $columns = ['*']): ?Model
    {
        return $this->model->newQuery()->find($id, $columns);
    }

    /**
     * Find a record by ID or fail.
     */
    public function findOrFail(string|int $id, array $columns = ['*']): Model
    {
        return $this->model->newQuery()->findOrFail($id, $columns);
    }

    /**
     * Find records by specific field.
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->where($field, $value)->get($columns);
    }

    /**
     * Find a single record by specific field.
     */
    public function findOneBy(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->model->newQuery()->where($field, $value)->first($columns);
    }

    /**
     * Create a new record.
     */
    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * Update a record.
     */
    public function update(string|int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);
        return $model;
    }

    /**
     * Delete a record.
     */
    public function delete(string|int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    /**
     * Count records.
     */
    public function count(): int
    {
        return $this->model->newQuery()->count();
    }

    /**
     * Check if record exists.
     */
    public function exists(string|int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }

    /**
     * Get a new query builder instance.
     */
    protected function query()
    {
        return $this->model->newQuery();
    }
}
