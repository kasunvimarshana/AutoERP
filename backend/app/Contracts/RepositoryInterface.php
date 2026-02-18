<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Interface
 *
 * Defines the contract for all repository implementations
 * following the Repository Pattern for data access abstraction.
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
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Find records by specific criteria
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection;

    /**
     * Find a single record by specific criteria
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record
     */
    public function delete(int $id): bool;

    /**
     * Count records matching criteria
     */
    public function count(array $criteria = []): int;

    /**
     * Check if record exists
     */
    public function exists(array $criteria): bool;
}
