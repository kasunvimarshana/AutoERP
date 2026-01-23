<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
     * @param array<string> $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated records
     *
     * @param int $perPage
     * @param array<string> $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find a record by ID
     *
     * @param int $id
     * @param array<string> $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find a record by ID or fail
     *
     * @param int $id
     * @param array<string> $columns
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Find records by criteria
     *
     * @param array<string, mixed> $criteria
     * @param array<string> $columns
     * @return Collection
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection;

    /**
     * Find first record by criteria
     *
     * @param array<string, mixed> $criteria
     * @param array<string> $columns
     * @return Model|null
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model;

    /**
     * Create a new record
     *
     * @param array<string, mixed> $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a record
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return Model
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Count records by criteria
     *
     * @param array<string, mixed> $criteria
     * @return int
     */
    public function count(array $criteria = []): int;

    /**
     * Check if record exists by criteria
     *
     * @param array<string, mixed> $criteria
     * @return bool
     */
    public function exists(array $criteria): bool;
}
