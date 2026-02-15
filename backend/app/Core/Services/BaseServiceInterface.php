<?php

namespace App\Core\Services;

interface BaseServiceInterface
{
    /**
     * Get all records with optional filtering and pagination
     *
     * @param array $filters
     * @param int|null $perPage
     * @return mixed
     */
    public function getAll(array $filters = [], ?int $perPage = null);

    /**
     * Get a single record by ID
     *
     * @param int $id
     * @return mixed
     */
    public function getById(int $id);

    /**
     * Create a new record
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data);

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
     * @return mixed
     */
    public function bulkCreate(array $data);

    /**
     * Bulk update records
     *
     * @param array $data
     * @return mixed
     */
    public function bulkUpdate(array $data);
}
