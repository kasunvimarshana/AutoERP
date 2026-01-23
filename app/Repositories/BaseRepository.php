<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;

/**
 * Base Repository Implementation
 *
 * Abstract base class providing common repository functionality.
 * All module-specific repositories should extend this class.
 *
 * @example
 * class CustomerRepository extends BaseRepository
 * {
 *     protected function model(): string
 *     {
 *         return Customer::class;
 *     }
 * }
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var mixed
     */
    protected $model;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->makeModel();
    }

    /**
     * Specify Model class name
     */
    abstract protected function model(): string;

    /**
     * Make Model instance
     */
    protected function makeModel(): void
    {
        $model = $this->model();
        $this->model = new ($model)();
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->all($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function find($id, array $columns = ['*']): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail($id, array $columns = ['*']): \Illuminate\Database\Eloquent\Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(array $criteria, array $columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model->query();

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
     * {@inheritDoc}
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?\Illuminate\Database\Eloquent\Model
    {
        $query = $this->model->query();

        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): \Illuminate\Database\Eloquent\Model
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update($id, array $data): \Illuminate\Database\Eloquent\Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id): bool
    {
        $record = $this->findOrFail($id);

        return $record->forceDelete();
    }

    /**
     * {@inheritDoc}
     */
    public function softDelete($id): bool
    {
        $record = $this->findOrFail($id);

        return $record->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function restore($id): bool
    {
        $record = $this->model->onlyTrashed()->findOrFail($id);

        return $record->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function exists($id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $criteria = []): int
    {
        $query = $this->model->query();

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
     * Begin a database transaction
     */
    protected function beginTransaction(): void
    {
        // DB::beginTransaction();
    }

    /**
     * Commit the database transaction
     */
    protected function commit(): void
    {
        // DB::commit();
    }

    /**
     * Rollback the database transaction
     */
    protected function rollback(): void
    {
        // DB::rollBack();
    }
}
