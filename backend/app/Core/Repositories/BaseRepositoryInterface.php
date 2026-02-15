<?php

namespace App\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Get all records
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get paginated records
     *
     * @param int $perPage
     * @param array $columns
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * Find a record by ID
     *
     * @param int $id
     * @param array $columns
     * @param array $relations
     * @return Model|null
     */
    public function findById(int $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find records by criteria
     *
     * @param array $criteria
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function findBy(array $criteria, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Find single record by criteria
     *
     * @param array $criteria
     * @param array $columns
     * @param array $relations
     * @return Model|null
     */
    public function findOneBy(array $criteria, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Create a new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Bulk create records
     *
     * @param array $data
     * @return bool
     */
    public function bulkCreate(array $data): bool;

    /**
     * Bulk update records
     *
     * @param array $data
     * @return bool
     */
    public function bulkUpdate(array $data): bool;

    /**
     * Count records by criteria
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int;

    /**
     * Check if record exists
     *
     * @param array $criteria
     * @return bool
     */
    public function exists(array $criteria): bool;
}
