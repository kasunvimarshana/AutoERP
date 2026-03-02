<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base repository contract.
 *
 * All module repositories must implement this interface.
 * Every method is tenant-aware (enforced via Eloquent global scope).
 */
interface RepositoryContract
{
    /**
     * Find a record by its primary key.
     */
    public function findById(int|string $id): ?Model;

    /**
     * Find a record by its primary key or throw ModelNotFoundException.
     */
    public function findOrFail(int|string $id): Model;

    /**
     * Return all records (tenant-scoped).
     */
    public function all(): Collection;

    /**
     * Return a paginated list of records (tenant-scoped).
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Persist a new record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Update an existing record by primary key.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data): Model;

    /**
     * Delete a record by primary key.
     */
    public function delete(int|string $id): bool;
}
