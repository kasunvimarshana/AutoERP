<?php

declare(strict_types=1);

namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Contracts\RepositoryInterface;

/**
 * Base Repository Implementation
 *
 * Provides common data access operations for all repositories.
 * Implements the Repository pattern to decouple data access from business logic.
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The model instance.
     */
    protected Model $model;

    /**
     * Create a new repository instance.
     *
     * Supports both constructor injection and makeModel() pattern.
     */
    public function __construct(?Model $model = null)
    {
        $this->model = $model ?? $this->makeModel();
    }

    /**
     * Make a new model instance.
     *
     * Override this method in child classes if not using constructor injection.
     */
    protected function makeModel(): Model
    {
        $class = $this->getModelClass();

        return app($class);
    }

    /**
     * Get the model class name.
     *
     * Override this method in child classes if not using constructor injection.
     */
    protected function getModelClass(): string
    {
        throw new \RuntimeException(
            'Either inject model in constructor or override makeModel() method in '.static::class
        );
    }

    /**
     * Find a record by ID.
     */
    public function find(int|string $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find a record by ID or fail.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Get all records.
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Create a new record.
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record.
     */
    public function update(int|string $id, array $data): bool
    {
        $model = $this->findOrFail($id);

        return $model->update($data);
    }

    /**
     * Delete a record.
     */
    public function delete(int|string $id): bool
    {
        $model = $this->findOrFail($id);

        return $model->delete();
    }

    /**
     * Find records by criteria.
     */
    public function findBy(array $criteria): Collection
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->get();
    }

    /**
     * Find a single record by criteria.
     */
    public function findOneBy(array $criteria): ?Model
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->first();
    }

    /**
     * Get paginated results.
     */
    public function paginate(int $perPage = 15, array $criteria = [], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (! empty($with)) {
            $query->with($with);
        }

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Search records.
     */
    public function search(string $term, array $fields, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        $query->where(function ($q) use ($term, $fields) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'like', "%{$term}%");
            }
        });

        return $query->paginate($perPage);
    }

    /**
     * Count records by criteria.
     */
    public function count(array $criteria = []): int
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->count();
    }

    /**
     * Check if a record exists.
     */
    public function exists(array $criteria): bool
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->exists();
    }

    /**
     * Get first record by criteria or create.
     */
    public function firstOrCreate(array $criteria, array $values = []): Model
    {
        return $this->model->firstOrCreate($criteria, $values);
    }

    /**
     * Update or create a record.
     */
    public function updateOrCreate(array $criteria, array $values): Model
    {
        return $this->model->updateOrCreate($criteria, $values);
    }

    /**
     * Bulk insert records.
     */
    public function bulkInsert(array $records): bool
    {
        return $this->model->insert($records);
    }

    /**
     * Bulk update records.
     */
    public function bulkUpdate(array $criteria, array $data): int
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->update($data);
    }

    /**
     * Delete records by criteria.
     */
    public function deleteBy(array $criteria): int
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->delete();
    }

    /**
     * Get fresh model instance.
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Begin a database transaction.
     */
    public function beginTransaction(): void
    {
        $this->model->getConnection()->beginTransaction();
    }

    /**
     * Commit a database transaction.
     */
    public function commit(): void
    {
        $this->model->getConnection()->commit();
    }

    /**
     * Rollback a database transaction.
     */
    public function rollback(): void
    {
        $this->model->getConnection()->rollBack();
    }
}
