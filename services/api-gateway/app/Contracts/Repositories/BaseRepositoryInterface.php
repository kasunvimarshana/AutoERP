<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Repository Interface
 *
 * Defines the contract for all repository implementations.
 * Supports CRUD, pagination, filtering, searching, and sorting.
 */
interface BaseRepositoryInterface
{
    /**
     * Find a record by its primary key.
     */
    public function findById(string|int $id, array $relations = []): ?Model;

    /**
     * Find a record by a specific field.
     */
    public function findBy(string $field, mixed $value, array $relations = []): ?Model;

    /**
     * Get all records with optional pagination.
     * Returns paginated results when 'per_page' is in params, otherwise returns all.
     *
     * @param  array<string, mixed>  $params  Supports: per_page, page, search, sort_by, sort_dir, filters
     */
    public function getAll(array $params = []): Collection|LengthAwarePaginator;

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Update an existing record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string|int $id, array $data): Model;

    /**
     * Delete a record (soft delete if supported).
     */
    public function delete(string|int $id): bool;

    /**
     * Restore a soft-deleted record.
     */
    public function restore(string|int $id): bool;

    /**
     * Paginate any iterable data source (collections, arrays, API responses).
     *
     * @param  mixed  $data  Array, Collection, or iterable
     * @param  array<string, mixed>  $params
     */
    public function paginateData(mixed $data, array $params = []): array|LengthAwarePaginator;
}
