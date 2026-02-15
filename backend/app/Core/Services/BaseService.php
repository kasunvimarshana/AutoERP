<?php

namespace App\Core\Services;

use App\Core\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Core\Exceptions\ServiceException;

abstract class BaseService implements BaseServiceInterface
{
    /**
     * @var BaseRepositoryInterface
     */
    protected $repository;

    /**
     * BaseService constructor.
     *
     * @param BaseRepositoryInterface $repository
     */
    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all records with optional filtering and pagination
     *
     * @param array $filters
     * @param int|null $perPage
     * @return mixed
     */
    public function getAll(array $filters = [], ?int $perPage = null)
    {
        try {
            if ($perPage) {
                return $this->repository->paginate($perPage);
            }
            
            return $this->repository->all();
        } catch (\Exception $e) {
            Log::error('Error in getAll: ' . $e->getMessage());
            throw new ServiceException('Failed to retrieve records: ' . $e->getMessage());
        }
    }

    /**
     * Get a single record by ID
     *
     * @param int $id
     * @return mixed
     */
    public function getById(int $id)
    {
        try {
            $record = $this->repository->findById($id);
            
            if (!$record) {
                throw new ServiceException('Record not found');
            }
            
            return $record;
        } catch (\Exception $e) {
            Log::error('Error in getById: ' . $e->getMessage());
            throw new ServiceException('Failed to retrieve record: ' . $e->getMessage());
        }
    }

    /**
     * Create a new record
     *
     * @param array $data
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
            Log::error('Error in create: ' . $e->getMessage());
            throw new ServiceException('Failed to create record: ' . $e->getMessage());
        }
    }

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data)
    {
        DB::beginTransaction();
        
        try {
            $success = $this->repository->update($id, $data);
            
            if (!$success) {
                throw new ServiceException('Record not found or update failed');
            }
            
            DB::commit();
            
            return $this->repository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in update: ' . $e->getMessage());
            throw new ServiceException('Failed to update record: ' . $e->getMessage());
        }
    }

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $success = $this->repository->delete($id);
            
            if (!$success) {
                throw new ServiceException('Record not found or delete failed');
            }
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in delete: ' . $e->getMessage());
            throw new ServiceException('Failed to delete record: ' . $e->getMessage());
        }
    }

    /**
     * Bulk create records
     *
     * @param array $data
     * @return mixed
     */
    public function bulkCreate(array $data)
    {
        DB::beginTransaction();
        
        try {
            $success = $this->repository->bulkCreate($data);
            
            DB::commit();
            
            return $success;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulkCreate: ' . $e->getMessage());
            throw new ServiceException('Failed to bulk create records: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update records
     *
     * @param array $data
     * @return mixed
     */
    public function bulkUpdate(array $data)
    {
        DB::beginTransaction();
        
        try {
            $success = $this->repository->bulkUpdate($data);
            
            DB::commit();
            
            return $success;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulkUpdate: ' . $e->getMessage());
            throw new ServiceException('Failed to bulk update records: ' . $e->getMessage());
        }
    }
}
