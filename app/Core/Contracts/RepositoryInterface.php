<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Repository Interface
 *
 * Defines the contract for all repository implementations
 * following the Repository pattern for data access abstraction
 */
interface RepositoryInterface
{
    /**
     * Get all records
     *
     * @param  array<string>  $columns
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated records
     *
     * @param  array<string>  $columns
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find a record by ID
     *
     * @param  array<string>  $columns
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find a record by ID or fail
     *
     * @param  array<string>  $columns
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Find records by criteria
     *
     * @param  array<string, mixed>  $criteria
     * @param  array<string>  $columns
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection;

    /**
     * Find first record by criteria
     *
     * @param  array<string, mixed>  $criteria
     * @param  array<string>  $columns
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model;

    /**
     * Create a new record
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Update a record
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete a record
     */
    public function delete(int $id): bool;

    /**
     * Count records by criteria
     *
     * @param  array<string, mixed>  $criteria
     */
    public function count(array $criteria = []): int;

    /**
     * Check if record exists by criteria
     *
     * @param  array<string, mixed>  $criteria
     */
    public function exists(array $criteria): bool;
}
