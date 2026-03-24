<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    /**
     * Find a single record by its primary key.
     *
     * @param  mixed  $id
     * @return mixed
     */
    public function find($id, array $columns = ['*']);

    /**
     * Get all records matching the current criteria.
     */
    public function get(array $columns = ['*']): Collection;

    /**
     * Paginate the results.
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator;

    /**
     * Create a new record.
     *
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update an existing record.
     *
     * @param  mixed  $id
     * @return mixed
     */
    public function update($id, array $data);

    /**
     * Delete a record.
     *
     * @param  mixed  $id
     */
    public function delete($id): bool;

    /**
     * Set the relationships that should be eager loaded.
     */
    public function with(array|string $relations): static;

    /**
     * Add a basic where clause.
     */
    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): static;

    /**
     * Add a "where in" clause.
     */
    public function whereIn(string $column, array $values, string $boolean = 'and', bool $not = false): static;

    /**
     * Add a "where between" clause.
     */
    public function whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false): static;

    /**
     * Add a "where null" clause.
     */
    public function whereNull(string $column, string $boolean = 'and', bool $not = false): static;

    /**
     * Add an order by clause.
     */
    public function orderBy(string $column, string $direction = 'asc'): static;

    /**
     * Add a raw order by clause.
     */
    public function orderByRaw(string $sql, array $bindings = []): static;

    /**
     * Set the limit.
     */
    public function limit(int $limit): static;

    /**
     * Set the offset.
     */
    public function offset(int $offset): static;
}
