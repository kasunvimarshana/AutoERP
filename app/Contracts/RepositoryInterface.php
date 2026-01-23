<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Interface
 *
 * Defines the contract for all repository implementations following
 * the Repository Pattern for data access abstraction.
 */
interface RepositoryInterface
{
    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated records
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find a record by ID
     */
    public function find($id, array $columns = ['*']): ?Model;

    /**
     * Find a record by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, array $columns = ['*']): Model;

    /**
     * Find records by criteria
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection;

    /**
     * Find a single record by criteria
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record
     */
    public function update($id, array $data): Model;

    /**
     * Delete a record
     */
    public function delete($id): bool;

    /**
     * Soft delete a record (if applicable)
     */
    public function softDelete($id): bool;

    /**
     * Restore a soft-deleted record
     */
    public function restore($id): bool;

    /**
     * Check if a record exists
     */
    public function exists($id): bool;

    /**
     * Get count of records
     */
    public function count(array $criteria = []): int;
}
