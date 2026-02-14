<?php

namespace App\Core\Services;

use App\Core\Interfaces\RepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Base Service Class
 *
 * Provides common business logic operations for all services
 * Enforces transactional integrity and error handling
 */
abstract class BaseService
{
    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * Get all records
     *
     * @return mixed
     */
    public function getAll(array $columns = ['*'])
    {
        try {
            return $this->repository->all($columns);
        } catch (\Exception $e) {
            Log::error('Error fetching all records: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get paginated records
     *
     * @return mixed
     */
    public function getPaginated(int $perPage = 15, array $columns = ['*'])
    {
        try {
            return $this->repository->paginate($perPage, $columns);
        } catch (\Exception $e) {
            Log::error('Error fetching paginated records: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Find a record by ID
     *
     * @return mixed
     */
    public function find(int $id, array $columns = ['*'])
    {
        try {
            return $this->repository->find($id, $columns);
        } catch (\Exception $e) {
            Log::error("Error finding record with ID {$id}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new record with transaction
     *
     * @return mixed
     */
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $record = $this->repository->create($data);
            DB::commit();

            return $record;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating record: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a record with transaction
     */
    public function update(int $id, array $data): bool
    {
        DB::beginTransaction();

        try {
            $result = $this->repository->update($id, $data);
            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating record with ID {$id}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a record with transaction
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();

        try {
            $result = $this->repository->delete($id);
            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting record with ID {$id}: ".$e->getMessage());
            throw $e;
        }
    }
}
