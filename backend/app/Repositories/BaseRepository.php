<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Implementation
 *
 * Abstract base class providing common repository functionality
 * for all concrete repository implementations.
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Specify Model class name
     */
    abstract protected function model(): string;

    /**
     * Make Model instance
     */
    protected function makeModel(): Model
    {
        $model = app($this->model());

        if (! $model instanceof Model) {
            throw new \RuntimeException(
                "Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model"
            );
        }

        return $model;
    }

    /**
     * Get a new query builder for the model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function query()
    {
        return $this->model->newQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->get($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->newQuery()->find($id, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->newQuery()->findOrFail($id, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection
    {
        $query = $this->model->newQuery();

        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->get($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model
    {
        $query = $this->model->newQuery();

        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first($columns);
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
    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);

        return $model->update($data);
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
    public function count(array $criteria = []): int
    {
        $query = $this->model->newQuery();

        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->count();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(array $criteria): bool
    {
        $query = $this->model->newQuery();

        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }

        return $query->exists();
    }

    /**
     * Begin a database transaction
     */
    protected function beginTransaction(): void
    {
        $this->model->getConnection()->beginTransaction();
    }

    /**
     * Commit the database transaction
     */
    protected function commit(): void
    {
        $this->model->getConnection()->commit();
    }

    /**
     * Rollback the database transaction
     */
    protected function rollback(): void
    {
        $this->model->getConnection()->rollBack();
    }
}
