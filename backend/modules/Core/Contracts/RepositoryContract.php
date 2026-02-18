<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Repository Contract for AutoERP
 *
 * Defines standard CRUD operations for all repositories.
 * Enforces tenant-aware data access and consistent interface across modules.
 * Implementations must use TenantContext for automatic tenant scoping.
 *
 * @package Modules\Core\Contracts
 */
interface RepositoryContract
{
    /**
     * Retrieve all records
     *
     * @param array<string> $columns
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Paginate records
     *
     * @param int $perPage
     * @param array<string> $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find record by ID
     *
     * @param int|string $id
     * @param array<string> $columns
     * @return Model|null
     */
    public function find($id, array $columns = ['*']): ?Model;

    /**
     * Find record by ID or fail
     *
     * @param int|string $id
     * @param array<string> $columns
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, array $columns = ['*']): Model;

    /**
     * Find record by field value
     *
     * @param string $field
     * @param mixed $value
     * @param array<string> $columns
     * @return Model|null
     */
    public function findBy(string $field, $value, array $columns = ['*']): ?Model;

    /**
     * Find all records matching field value
     *
     * @param string $field
     * @param mixed $value
     * @param array<string> $columns
     * @return Collection<int, Model>
     */
    public function findAllBy(string $field, $value, array $columns = ['*']): Collection;

    /**
     * Create new record
     *
     * @param array<string, mixed> $attributes
     * @return Model
     */
    public function create(array $attributes): Model;

    /**
     * Update existing record
     *
     * @param Model $model
     * @param array<string, mixed> $attributes
     * @return bool
     */
    public function update(Model $model, array $attributes): bool;

    /**
     * Delete record (soft delete if supported)
     *
     * @param Model $model
     * @return bool
     */
    public function delete(Model $model): bool;

    /**
     * Permanently delete record
     *
     * @param Model $model
     * @return bool
     */
    public function forceDelete(Model $model): bool;

    /**
     * Restore soft-deleted record
     *
     * @param Model $model
     * @return bool
     */
    public function restore(Model $model): bool;

    /**
     * Eager load relationships
     *
     * @param array<string> $relations
     * @return static
     */
    public function with(array $relations): self;

    /**
     * Filter records by field values
     *
     * @param string $field
     * @param array<mixed> $values
     * @return static
     */
    public function whereIn(string $field, array $values): self;

    /**
     * Order records by column
     *
     * @param string $column
     * @param string $direction
     * @return static
     */
    public function orderBy(string $column, string $direction = 'asc'): self;

    /**
     * Get new query builder instance
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery();
}
