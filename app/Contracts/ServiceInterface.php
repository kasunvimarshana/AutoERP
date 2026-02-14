<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Base Service Interface
 * 
 * Defines the contract for all service layer implementations.
 * Services contain business logic and orchestrate between controllers
 * and repositories, handling transactions and complex operations.
 */
interface ServiceInterface
{
    /**
     * Get all records with optional configuration
     *
     * @param array $config Query configuration
     * @return Collection
     */
    public function getAll(array $config = []): Collection;

    /**
     * Get paginated records
     *
     * @param int $perPage Number of items per page
     * @param array $config Query configuration
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, array $config = []): LengthAwarePaginator;

    /**
     * Get a single record by ID
     *
     * @param int|string $id
     * @param array $relations Relations to eager load
     * @return Model|null
     */
    public function getById(int|string $id, array $relations = []): ?Model;

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
}
