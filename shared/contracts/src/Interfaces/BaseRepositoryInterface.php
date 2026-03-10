<?php

declare(strict_types=1);

namespace KvSaas\Contracts\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * BaseRepositoryInterface
 *
 * Defines the contract for the fully dynamic, reusable base repository
 * that all microservice repositories must implement or extend.
 *
 * Supports:
 *  - Dynamic CRUD operations
 *  - Conditional pagination (page/per_page/columns/pageName)
 *  - Filtering, searching, sorting
 *  - Eager loading of relations
 *  - Cross-service data access via custom resolvers
 *  - Queries, arrays, collections, or API responses
 */
interface BaseRepositoryInterface
{
    /**
     * Retrieve all records, optionally filtered and sorted.
     *
     * @param  array<string, mixed>  $filters   Key-value filter conditions
     * @param  array<string>         $columns   Columns to select (default: ['*'])
     * @param  array<string>         $relations Eager-load relations
     * @param  array<string, string> $orderBy   Column => direction pairs
     * @return Collection<int, Model>
     */
    public function all(
        array $filters   = [],
        array $columns   = ['*'],
        array $relations = [],
        array $orderBy   = []
    ): Collection;

    /**
     * Retrieve a paginated result set.
     *
     * @param  array<string, mixed>  $filters
     * @param  int                   $perPage   Records per page
     * @param  array<string>         $columns
     * @param  string                $pageName  Query parameter name for the page number
     * @param  int|null              $page      Current page (null = auto-detect from request)
     * @param  array<string>         $relations
     * @param  array<string, string> $orderBy
     * @return LengthAwarePaginator
     */
    public function paginate(
        array  $filters   = [],
        int    $perPage   = 15,
        array  $columns   = ['*'],
        string $pageName  = 'page',
        ?int   $page      = null,
        array  $relations = [],
        array  $orderBy   = []
    ): LengthAwarePaginator;

    /**
     * Find a record by its primary key.
     *
     * @param  int|string            $id
     * @param  array<string>         $columns
     * @param  array<string>         $relations
     * @return Model|null
     */
    public function find(
        int|string $id,
        array      $columns   = ['*'],
        array      $relations = []
    ): ?Model;

    /**
     * Find a record by primary key or throw a ModelNotFoundException.
     *
     * @param  int|string    $id
     * @param  array<string> $columns
     * @param  array<string> $relations
     * @return Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(
        int|string $id,
        array      $columns   = ['*'],
        array      $relations = []
    ): Model;

    /**
     * Find the first record matching the given criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @param  array<string>         $columns
     * @param  array<string>         $relations
     * @return Model|null
     */
    public function findBy(
        array $criteria,
        array $columns   = ['*'],
        array $relations = []
    ): ?Model;

    /**
     * Find all records matching the given criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @param  array<string>         $columns
     * @param  array<string>         $relations
     * @param  array<string, string> $orderBy
     * @return Collection<int, Model>
     */
    public function findAllBy(
        array $criteria,
        array $columns   = ['*'],
        array $relations = [],
        array $orderBy   = []
    ): Collection;

    /**
     * Create a new record.
     *
     * @param  array<string, mixed> $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a record by its primary key.
     *
     * @param  int|string           $id
     * @param  array<string, mixed> $data
     * @return Model
     */
    public function update(int|string $id, array $data): Model;

    /**
     * Update or create a record matching the given attributes.
     *
     * @param  array<string, mixed> $attributes  Match conditions
     * @param  array<string, mixed> $values      Values to set or update
     * @return Model
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * Delete a record by its primary key.
     *
     * @param  int|string $id
     * @return bool
     */
    public function delete(int|string $id): bool;

    /**
     * Soft-delete a record (requires SoftDeletes trait on model).
     *
     * @param  int|string $id
     * @return bool
     */
    public function softDelete(int|string $id): bool;

    /**
     * Restore a soft-deleted record.
     *
     * @param  int|string $id
     * @return bool
     */
    public function restore(int|string $id): bool;

    /**
     * Count records matching the given criteria.
     *
     * @param  array<string, mixed> $criteria
     * @return int
     */
    public function count(array $criteria = []): int;

    /**
     * Bulk insert records.
     *
     * @param  array<int, array<string, mixed>> $data
     * @return bool
     */
    public function bulkInsert(array $data): bool;

    /**
     * Perform a full-text or LIKE search across the specified columns.
     *
     * @param  string                $term      Search term
     * @param  array<string>         $columns   Columns to search
     * @param  array<string, mixed>  $filters   Additional filters
     * @param  int                   $perPage
     * @param  array<string>         $relations
     * @return LengthAwarePaginator
     */
    public function search(
        string $term,
        array  $columns,
        array  $filters   = [],
        int    $perPage   = 15,
        array  $relations = []
    ): LengthAwarePaginator;

    /**
     * Execute a raw query and return results as a collection.
     *
     * @param  string        $query
     * @param  array<mixed>  $bindings
     * @return Collection<int, Model>
     */
    public function rawQuery(string $query, array $bindings = []): Collection;

    /**
     * Eager-load relations onto an existing collection or model.
     *
     * @param  Model|Collection<int, Model>  $resource
     * @param  array<string>                 $relations
     * @return Model|Collection<int, Model>
     */
    public function loadRelations(Model|Collection $resource, array $relations): Model|Collection;

    /**
     * Begin a database transaction.
     *
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * Commit the current database transaction.
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Roll back the current database transaction.
     *
     * @return void
     */
    public function rollBack(): void;
}
