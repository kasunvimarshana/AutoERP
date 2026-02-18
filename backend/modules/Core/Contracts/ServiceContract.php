<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Base Service Contract for AutoERP
 *
 * Defines standard business logic operations for all services.
 * All domain services should implement this contract to ensure
 * consistent behavior across modules and enable dependency inversion.
 *
 * @package Modules\Core\Contracts
 */
interface ServiceContract
{
    /**
     * Retrieve entity by ID
     *
     * @param int|string $id
     * @return mixed Entity DTO or Model
     */
    public function find($id);

    /**
     * Retrieve all entities with optional filtering
     *
     * @param array<string, mixed> $filters
     * @param int|null $perPage
     * @return \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(array $filters = [], ?int $perPage = null);

    /**
     * Create new entity
     *
     * @param array<string, mixed> $data
     * @return mixed Entity DTO or Model
     */
    public function create(array $data);

    /**
     * Update existing entity
     *
     * @param int|string $id
     * @param array<string, mixed> $data
     * @return mixed Updated entity DTO or Model
     */
    public function update($id, array $data);

    /**
     * Delete entity
     *
     * @param int|string $id
     * @return bool
     */
    public function delete($id): bool;
}
