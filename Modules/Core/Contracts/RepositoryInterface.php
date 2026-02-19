<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface RepositoryInterface
 *
 * Base repository contract for all data access operations
 */
interface RepositoryInterface
{
    /**
     * Find a record by ID
     */
    public function find(int|string $id): ?Model;

    /**
     * Get all records
     */
    public function all(): Collection;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record
     */
    public function update(int|string $id, array $data): bool;

    /**
     * Delete a record
     */
    public function delete(int|string $id): bool;

    /**
     * Find records by criteria
     */
    public function findBy(array $criteria): Collection;

    /**
     * Find a single record by criteria
     */
    public function findOneBy(array $criteria): ?Model;
}
