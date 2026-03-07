<?php

namespace App\Repositories\Contracts;

interface BaseRepositoryInterface
{
    /**
     * Retrieve all records.
     */
    public function all(array $columns = ['*']): \Illuminate\Database\Eloquent\Collection;

    /**
     * Find a record by primary key.
     */
    public function find(int|string $id, array $columns = ['*']): ?\Illuminate\Database\Eloquent\Model;

    /**
     * Find records matching a column/value pair.
     */
    public function findBy(string $column, mixed $value, array $columns = ['*']): \Illuminate\Database\Eloquent\Collection;

    /**
     * Create a new record.
     */
    public function create(array $data): \Illuminate\Database\Eloquent\Model;

    /**
     * Update a record by primary key.
     */
    public function update(int|string $id, array $data): \Illuminate\Database\Eloquent\Model;

    /**
     * Delete a record by primary key.
     */
    public function delete(int|string $id): bool;

    /**
     * Paginate all records.
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Full-text search across specified columns.
     */
    public function search(string $term, array $columns): \Illuminate\Database\Eloquent\Collection;

    /**
     * Apply dynamic where-clause filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function filter(array $filters): \Illuminate\Database\Eloquent\Collection;

    /**
     * Apply dynamic sorting.
     */
    public function sort(string $column, string $direction = 'asc'): \Illuminate\Database\Eloquent\Collection;

    /**
     * Return paginated results when `per_page` is present in $request, otherwise return all.
     */
    public function paginateConditional(
        \Illuminate\Database\Eloquent\Builder $query,
        \Illuminate\Http\Request $request
    ): \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
