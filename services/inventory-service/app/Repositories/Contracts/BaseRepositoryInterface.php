<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface BaseRepositoryInterface
{
    /**
     * Retrieve all records.
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Find a record by primary key.
     */
    public function find(int|string $id, array $columns = ['*']): ?Model;

    /**
     * Find records matching a column/value pair.
     */
    public function findBy(string $column, mixed $value, array $columns = ['*']): Collection;

    /**
     * Create a new record.
     */
    public function create(array $data): Model;

    /**
     * Update a record by primary key.
     */
    public function update(int|string $id, array $data): Model;

    /**
     * Delete a record by primary key.
     */
    public function delete(int|string $id): bool;

    /**
     * Paginate all records.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Full-text search across specified columns.
     */
    public function search(string $term, array $columns): Collection;

    /**
     * Apply dynamic where-clause filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function filter(array $filters): Collection;

    /**
     * Apply dynamic sorting.
     */
    public function sort(string $column, string $direction = 'asc'): Collection;

    /**
     * Return paginated results when `per_page` is present in $request, otherwise return all.
     */
    public function paginateConditional(Builder $query, Request $request): Collection|LengthAwarePaginator;

    /**
     * Eager-load relationships onto the base query.
     */
    public function withRelations(array $relations): static;

    /**
     * Perform an HTTP request to a remote microservice.
     *
     * @param  array<string, mixed>  $params
     */
    public function crossServiceFetch(string $serviceUrl, string $endpoint, array $params = []): mixed;
}
