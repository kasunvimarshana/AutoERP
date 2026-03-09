<?php

declare(strict_types=1);

namespace Shared\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Base Repository Interface
 * 
 * Defines the contract for all repository implementations.
 * Supports CRUD operations, filtering, searching, sorting, and conditional pagination.
 */
interface RepositoryInterface
{
    /**
     * Find a record by its primary key.
     */
    public function findById(int|string $id, array $relations = []): ?Model;

    /**
     * Find a record by specific criteria.
     */
    public function findBy(array $criteria, array $relations = []): ?Model;

    /**
     * Get all records matching criteria with optional pagination.
     * Returns paginated results when 'per_page' exists in filters, all results otherwise.
     */
    public function all(array $filters = [], array $relations = []): Collection|LengthAwarePaginator;

    /**
     * Create a new record.
     */
    public function create(array $data): Model;

    /**
     * Update an existing record.
     */
    public function update(int|string $id, array $data): Model;

    /**
     * Delete a record.
     */
    public function delete(int|string $id): bool;

    /**
     * Check if a record exists.
     */
    public function exists(array $criteria): bool;

    /**
     * Count records matching criteria.
     */
    public function count(array $criteria = []): int;
}
