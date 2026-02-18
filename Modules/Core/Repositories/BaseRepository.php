<?php

declare(strict_types=1);

namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Base Repository Implementation
 *
 * Provides a base implementation of the repository pattern for all entity repositories.
 * Implements common CRUD operations and query methods following Clean Architecture principles.
 *
 * This implementation:
 * - Abstracts database operations from business logic
 * - Provides consistent interface across all repositories
 * - Enables easy testing and mocking
 * - Supports both integer and UUID primary keys
 *
 * Usage:
 * 1. Extend this class in your module's repository
 * 2. Inject your model in the constructor
 * 3. Add domain-specific query methods
 *
 * Example:
 * class CustomerRepository extends BaseRepository {
 *     public function __construct(Customer $model) {
 *         parent::__construct($model);
 *     }
 *
 *     public function findActiveCustomers(): Collection {
 *         return $this->model->where('status', 'active')->get();
 *     }
 * }
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * The model instance.
     */
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
    public function findById(int|string $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria): ?Model
    {
        return $this->model->where($criteria)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int|string $id, array $data): Model
    {
        $model = $this->findById($id);

        if (! $model) {
            throw new ModelNotFoundException("Model with ID {$id} not found.");
        }

        $model->update($data);

        return $model->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int|string $id): bool
    {
        $model = $this->findById($id);

        if (! $model) {
            return false;
        }

        return (bool) $model->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection
    {
        return $this->model->where($criteria)->get($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function findWherePaginated(array $criteria, int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->where($criteria)->paginate($perPage, $columns);
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
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }
}
