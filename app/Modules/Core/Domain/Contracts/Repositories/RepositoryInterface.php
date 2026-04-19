<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Contracts\Repositories;

/**
 * Domain-layer repository contract.
 *
 * Uses plain PHP types (iterable, array) to avoid coupling the Domain
 * to any framework (Eloquent, Laravel pagination, etc.).
 * Infrastructure implementations may return framework-specific types
 * that satisfy these signatures (e.g. Collection implements iterable).
 */
interface RepositoryInterface
{
    /**
     * Find a single record by its primary key.
     */
    public function find(int|string $id, array $columns = ['*']): mixed;

    /**
     * Get all records matching the current criteria.
     *
     * @return iterable<int, mixed>
     */
    public function get(array $columns = ['*']): iterable;

    /**
     * Paginate the results.
     *
     * Returns a paginated result. Concrete implementations may return
     * framework-specific paginators that satisfy this contract.
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): mixed;

    /**
     * Create a new record.
     */
    public function create(array $data): mixed;

    /**
     * Update an existing record.
     */
    public function update(int|string $id, array $data): mixed;

    /**
     * Delete a record.
     */
    public function delete(int|string $id): bool;

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
     * Set the limit.
     */
    public function limit(int $limit): static;

    /**
     * Set the offset.
     */
    public function offset(int $offset): static;

    /**
     * Reset accumulated query criteria on the repository instance.
     */
    public function resetCriteria(): static;
}
