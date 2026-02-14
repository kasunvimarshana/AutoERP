<?php

namespace App\Core\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Repository Interface
 *
 * Defines standard CRUD operations that all repositories must implement.
 * Ensures consistency across all data access layers.
 */
interface RepositoryInterface
{
    /**
     * Find a model by its primary key.
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Get all models.
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated models.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Create a new model.
     */
    public function create(array $attributes): Model;

    /**
     * Update a model.
     */
    public function update(int $id, array $attributes): bool;

    /**
     * Delete a model.
     */
    public function delete(int $id): bool;

    /**
     * Find models by specific criteria.
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection;

    /**
     * Find first model by specific criteria.
     */
    public function findWhereFirst(array $criteria, array $columns = ['*']): ?Model;
}
