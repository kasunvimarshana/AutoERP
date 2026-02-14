<?php

namespace App\Core;

use App\DTOs\QueryConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Class BaseService
 * 
 * Base service class providing transaction management, common business logic patterns,
 * and CRUD orchestration using the enhanced repository layer
 * 
 * @package App\Core
 */
abstract class BaseService
{
    /**
     * The repository instance
     *
     * @var BaseRepositoryInterface
     */
    protected BaseRepositoryInterface $repository;

    /**
     * Execute a callback within a database transaction
     *
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    protected function transaction(callable $callback): mixed
    {
        try {
            DB::beginTransaction();
            
            $result = $callback();
            
            DB::commit();
            
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Transaction failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Log service activity
     *
     * @param string $action
     * @param array $context
     * @return void
     */
    protected function logActivity(string $action, array $context = []): void
    {
        Log::info($action, array_merge([
            'service' => static::class,
            'timestamp' => now()->toDateTimeString()
        ], $context));
    }

    /**
     * Handle service exception
     *
     * @param Exception $e
     * @param string $message
     * @return void
     * @throws Exception
     */
    protected function handleException(Exception $e, string $message): void
    {
        Log::error($message, [
            'exception' => $e->getMessage(),
            'service' => static::class,
            'trace' => $e->getTraceAsString()
        ]);
        
        throw new Exception($message . ': ' . $e->getMessage(), 0, $e);
    }

    /**
     * Validate required data fields
     *
     * @param array $data
     * @param array $required
     * @return void
     * @throws Exception
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missing));
        }
    }

    /**
     * Query with dynamic configuration
     *
     * @param QueryConfig $config
     * @return mixed
     */
    public function query(QueryConfig $config)
    {
        return $this->repository->query($config);
    }

    /**
     * Get all records with optional relations
     *
     * @param array $with
     * @return mixed
     */
    public function getAll(array $with = [])
    {
        if (!empty($with)) {
            return $this->repository->with($with)->all();
        }
        
        return $this->repository->all();
    }

    /**
     * Get paginated records
     *
     * @param int $perPage
     * @param array $with
     * @return mixed
     */
    public function getPaginated(int $perPage = 15, array $with = [])
    {
        if (!empty($with)) {
            return $this->repository->with($with)->paginate($perPage);
        }
        
        return $this->repository->paginate($perPage);
    }

    /**
     * Find record by ID
     *
     * @param int $id
     * @param array $with
     * @return mixed
     */
    public function findById(int $id, array $with = [])
    {
        if (!empty($with)) {
            return $this->repository->with($with)->find($id);
        }
        
        return $this->repository->find($id);
    }

    /**
     * Create a new record with transaction
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function create(array $data)
    {
        return $this->transaction(function () use ($data) {
            $record = $this->repository->create($data);
            
            $this->logActivity('Record created', [
                'id' => $record->id,
                'data' => $data
            ]);
            
            return $record;
        });
    }

    /**
     * Update a record with transaction
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function update(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $this->logActivity('Record updated', [
                    'id' => $id,
                    'data' => $data
                ]);
            }
            
            return $result;
        });
    }

    /**
     * Delete a record with transaction
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            
            if ($result) {
                $this->logActivity('Record deleted', ['id' => $id]);
            }
            
            return $result;
        });
    }

    /**
     * Find records by criteria
     *
     * @param array $criteria
     * @return mixed
     */
    public function findWhere(array $criteria)
    {
        return $this->repository->findWhere($criteria);
    }

    /**
     * Find single record by field
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    public function findBy(string $field, mixed $value)
    {
        return $this->repository->findBy($field, $value);
    }
}
