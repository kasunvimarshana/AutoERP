<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Retrieve all records.
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Find a record by primary key.
     */
    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find a record by a specific field value.
     */
    public function findBy(string $field, mixed $value, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find all records matching a field/value pair.
     */
    public function findAllBy(string $field, mixed $value, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Find records by multiple conditions.
     */
    public function findWhere(array $conditions, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Find the first record matching conditions.
     */
    public function firstWhere(array $conditions, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Create a new record.
     */
    public function create(array $data): Model;

    /**
     * Update a record by primary key.
     */
    public function update(int $id, array $data): Model;

    /**
     * Update records matching conditions.
     */
    public function updateWhere(array $conditions, array $data): int;

    /**
     * Delete a record by primary key.
     */
    public function delete(int $id): bool;

    /**
     * Delete records matching conditions.
     */
    public function deleteWhere(array $conditions): int;

    /**
     * Soft delete (if model uses SoftDeletes).
     */
    public function softDelete(int $id): bool;

    /**
     * Restore a soft-deleted record.
     */
    public function restore(int $id): bool;

    /**
     * Paginate records with optional filtering, sorting, and searching.
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        array $relations = [],
        array $filters = [],
        string $sortBy = 'id',
        string $sortDirection = 'asc',
        string $search = ''
    ): LengthAwarePaginator;

    /**
     * Apply filter conditions to a query.
     */
    public function applyFilters(Builder $query, array $filters): Builder;

    /**
     * Apply full-text search across searchable columns.
     */
    public function applySearch(Builder $query, string $search, array $searchableColumns): Builder;

    /**
     * Apply sorting to a query.
     */
    public function applySort(Builder $query, string $sortBy, string $sortDirection): Builder;

    /**
     * Count records matching conditions.
     */
    public function count(array $conditions = []): int;

    /**
     * Check if a record exists.
     */
    public function exists(array $conditions): bool;

    /**
     * Get records with eager-loaded relations.
     */
    public function with(array $relations): static;

    /**
     * Begin a database transaction.
     */
    public function beginTransaction(): void;

    /**
     * Commit a database transaction.
     */
    public function commit(): void;

    /**
     * Rollback a database transaction.
     */
    public function rollback(): void;

    /**
     * Execute a callable within an ACID transaction.
     */
    public function transaction(callable $callback): mixed;

    /**
     * Cross-service lookup: delegates to another registered repository/service.
     */
    public function findAcrossServices(string $service, int $id): mixed;

    /**
     * Bulk insert records.
     */
    public function bulkCreate(array $records): bool;

    /**
     * Bulk update records.
     */
    public function bulkUpdate(array $records, string $uniqueBy = 'id'): int;

    /**
     * Get the underlying Eloquent model instance.
     */
    public function getModel(): Model;

    /**
     * Return a new query builder for this repository's model.
     */
    public function newQuery(): Builder;
}
