<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Base Repository Interface
 *
 * Fully dynamic, reusable contract for all repository implementations.
 * Supports CRUD, conditional pagination, filtering, searching, sorting,
 * and cross-service data access.
 *
 * Returns paginated results when 'per_page' exists in filters,
 * all results otherwise.
 */
interface BaseRepositoryInterface
{
    /**
     * Find record by primary key.
     *
     * @param int|string $id
     * @param array<string> $relations
     */
    public function findById(int|string $id, array $relations = []): ?Model;

    /**
     * Find a single record matching given criteria.
     *
     * @param array<string, mixed> $criteria
     * @param array<string> $relations
     */
    public function findBy(array $criteria, array $relations = []): ?Model;

    /**
     * Get all records with optional filtering/sorting/pagination.
     * Returns LengthAwarePaginator when 'per_page' key is present in $filters,
     * Collection otherwise.
     *
     * @param array<string, mixed> $filters  Supports: search, sort_by, sort_dir, per_page, page,
     *                                        and any column => value pairs
     * @param array<string> $relations
     */
    public function all(array $filters = [], array $relations = []): Collection|LengthAwarePaginator;

    /**
     * Create a new record.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Model;

    /**
     * Update an existing record.
     *
     * @param int|string $id
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): Model;

    /**
     * Delete a record by primary key.
     */
    public function delete(int|string $id): bool;

    /**
     * Check if a record matching criteria exists.
     *
     * @param array<string, mixed> $criteria
     */
    public function exists(array $criteria): bool;

    /**
     * Count records matching criteria.
     *
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria = []): int;

    /**
     * Paginate an arbitrary iterable (array, collection, or API response).
     * Provides consistent pagination across heterogeneous data sources.
     *
     * @param iterable<mixed> $data
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function paginateIterable(iterable $data, int $perPage, int $page = 1): LengthAwarePaginator;

    /**
     * Bulk create multiple records in a single transaction.
     *
     * @param array<array<string, mixed>> $records
     * @return bool
     */
    public function bulkCreate(array $records): bool;

    /**
     * Find multiple records by their primary keys.
     *
     * @param array<int|string> $ids
     * @param array<string> $relations
     * @return Collection
     */
    public function findMany(array $ids, array $relations = []): Collection;
}
