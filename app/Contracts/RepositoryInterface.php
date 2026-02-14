<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Base Repository Interface
 * 
 * Defines the contract for all repository implementations in the system.
 * Repositories encapsulate data access logic and provide a clean abstraction
 * layer between the service layer and the data models.
 */
interface RepositoryInterface
{
    /**
     * Get all records with optional query configuration
     *
     * @param array $config Query configuration (relations, columns, filters, etc.)
     * @return Collection
     */
    public function all(array $config = []): Collection;

    /**
     * Get paginated records with optional query configuration
     *
     * @param int $perPage Number of items per page
     * @param array $config Query configuration
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $config = []): LengthAwarePaginator;

    /**
     * Find a record by ID
     *
     * @param int|string $id
     * @param array $relations Relations to eager load
     * @param array $columns Columns to select
     * @return Model|null
     */
    public function find(int|string $id, array $relations = [], array $columns = ['*']): ?Model;

    /**
     * Find a record by ID or fail
     *
     * @param int|string $id
     * @param array $relations Relations to eager load
     * @param array $columns Columns to select
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int|string $id, array $relations = [], array $columns = ['*']): Model;

    /**
     * Find records by specific field
     *
     * @param string $field
     * @param mixed $value
     * @param array $config Query configuration
     * @return Collection
     */
    public function findBy(string $field, mixed $value, array $config = []): Collection;

    /**
     * Find first record by specific field
     *
     * @param string $field
     * @param mixed $value
     * @param array $relations Relations to eager load
     * @param array $columns Columns to select
     * @return Model|null
     */
    public function findFirstBy(string $field, mixed $value, array $relations = [], array $columns = ['*']): ?Model;

    /**
     * Create a new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update an existing record
     *
     * @param int|string $id
     * @param array $data
     * @return Model
     */
    public function update(int|string $id, array $data): Model;

    /**
     * Delete a record
     *
     * @param int|string $id
     * @return bool
     */
    public function delete(int|string $id): bool;

    /**
     * Bulk delete records
     *
     * @param array $ids
     * @return int Number of deleted records
     */
    public function bulkDelete(array $ids): int;

    /**
     * Count records with optional filters
     *
     * @param array $filters
     * @return int
     */
    public function count(array $filters = []): int;

    /**
     * Check if record exists
     *
     * @param string $field
     * @param mixed $value
     * @param int|string|null $excludeId ID to exclude from check
     * @return bool
     */
    public function exists(string $field, mixed $value, int|string|null $excludeId = null): bool;

    /**
     * Apply query configuration (filters, search, sorts, etc.)
     *
     * @param array $config
     * @return self
     */
    public function applyConfig(array $config): self;

    /**
     * Reset query builder to fresh state
     *
     * @return self
     */
    public function resetQuery(): self;
}
