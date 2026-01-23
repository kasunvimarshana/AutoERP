<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Base Service Interface
 * 
 * Defines the contract for all service implementations
 * Services contain business logic and orchestrate repositories
 */
interface ServiceInterface
{
    /**
     * Get all records
     *
     * @param array<string, mixed> $filters
     * @return mixed
     */
    public function getAll(array $filters = []): mixed;

    /**
     * Get a single record by ID
     *
     * @param int $id
     * @return mixed
     */
    public function getById(int $id): mixed;

    /**
     * Create a new record
     *
     * @param array<string, mixed> $data
     * @return mixed
     */
    public function create(array $data): mixed;

    /**
     * Update an existing record
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return mixed
     */
    public function update(int $id, array $data): mixed;

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
