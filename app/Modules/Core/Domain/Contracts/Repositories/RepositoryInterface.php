<?php

namespace Modules\Core\Domain\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    /**
     * Find a single record by its primary key.
     *
     * @param mixed $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, array $columns = ['*']);

    /**
     * Get all records matching the current criteria.
     *
     * @param array $columns
     * @return Collection
     */
    public function get(array $columns = ['*']): Collection;

    /**
     * Paginate the results.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string|null $pageName
     * @param int|null $page
     * @return LengthAwarePaginator
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator;

    /**
     * Create a new record.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update an existing record.
     *
     * @param mixed $id
     * @param array $data
     * @return mixed
     */
    public function update($id, array $data);

    /**
     * Delete a record.
     *
     * @param mixed $id
     * @return bool
     */
    public function delete($id): bool;

    /**
     * Set the relationships that should be eager loaded.
     *
     * @param array|string $relations
     * @return static
     */
    public function with(array|string $relations): static;

    /**
     * Add a basic where clause.
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return static
     */
    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): static;

    /**
     * Add a "where in" clause.
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return static
     */
    public function whereIn(string $column, array $values, string $boolean = 'and', bool $not = false): static;

    /**
     * Add a "where between" clause.
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return static
     */
    public function whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false): static;

    /**
     * Add a "where null" clause.
     *
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return static
     */
    public function whereNull(string $column, string $boolean = 'and', bool $not = false): static;

    /**
     * Add an order by clause.
     *
     * @param string $column
     * @param string $direction
     * @return static
     */
    public function orderBy(string $column, string $direction = 'asc'): static;

    /**
     * Add a raw order by clause.
     *
     * @param string $sql
     * @param array $bindings
     * @return static
     */
    public function orderByRaw(string $sql, array $bindings = []): static;

    /**
     * Set the limit.
     *
     * @param int $limit
     * @return static
     */
    public function limit(int $limit): static;

    /**
     * Set the offset.
     *
     * @param int $offset
     * @return static
     */
    public function offset(int $offset): static;
}
