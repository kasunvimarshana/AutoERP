<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Searchable Repository Interface
 *
 * Extends base repository with search and pagination capabilities.
 * All repositories that support searching and filtering should implement this.
 */
interface SearchableRepositoryInterface extends RepositoryInterface
{
    /**
     * Paginate all records.
     *
     * @param  int  $perPage  Number of records per page
     * @param  array  $columns  Columns to select
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Search records by query.
     *
     * @param  string  $query  Search query
     * @param  array  $searchFields  Fields to search in
     * @param  int  $perPage  Number of records per page
     * @return LengthAwarePaginator
     */
    public function search(string $query, array $searchFields = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Filter records by multiple criteria with pagination.
     *
     * @param  array  $filters  Filters to apply
     * @param  int  $perPage  Number of records per page
     * @param  array  $orderBy  Order by column and direction
     * @return LengthAwarePaginator
     */
    public function filter(array $filters = [], int $perPage = 15, array $orderBy = []): LengthAwarePaginator;

    /**
     * Get records with relationships loaded.
     *
     * @param  array  $relations  Relations to eager load
     * @return Collection
     */
    public function with(array $relations): Collection;

    /**
     * Count records matching criteria.
     *
     * @param  array  $criteria  Filter criteria
     * @return int
     */
    public function count(array $criteria = []): int;

    /**
     * Check if record exists by criteria.
     *
     * @param  array  $criteria  Filter criteria
     * @return bool
     */
    public function exists(array $criteria): bool;
}
