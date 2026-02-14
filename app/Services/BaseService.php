<?php

namespace App\Services;

use App\Contracts\RepositoryInterface;
use App\Contracts\ServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Base Service Implementation
 * 
 * Provides common business logic functionality for all services.
 * Handles transactions, error handling, and orchestrates repository operations.
 * All cross-module interactions should go through service layers only.
 */
abstract class BaseService implements ServiceInterface
{
    /**
     * The repository instance
     */
    protected RepositoryInterface $repository;

    /**
     * Constructor
     *
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(array $config = []): Collection
    {
        try {
            return $this->repository->all($config);
        } catch (\Exception $e) {
            Log::error('Error getting all records', [
                'service' => static::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPaginated(int $perPage = 15, array $config = []): LengthAwarePaginator
    {
        try {
            return $this->repository->paginate($perPage, $config);
        } catch (\Exception $e) {
            Log::error('Error getting paginated records', [
                'service' => static::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getById(int|string $id, array $relations = []): ?Model
    {
        try {
            return $this->repository->find($id, $relations);
        } catch (\Exception $e) {
            Log::error('Error getting record by ID', [
                'service' => static::class,
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        DB::beginTransaction();
        
        try {
            // Hook: Before create
            $data = $this->beforeCreate($data);
            
            // Create the record
            $model = $this->repository->create($data);
            
            // Hook: After create
            $this->afterCreate($model, $data);
            
            DB::commit();
            
            return $model->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating record', [
                'service' => static::class,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(int|string $id, array $data): Model
    {
        DB::beginTransaction();
        
        try {
            // Get existing model
            $existingModel = $this->repository->findOrFail($id);
            
            // Hook: Before update
            $data = $this->beforeUpdate($existingModel, $data);
            
            // Update the record
            $model = $this->repository->update($id, $data);
            
            // Hook: After update
            $this->afterUpdate($model, $existingModel, $data);
            
            DB::commit();
            
            return $model->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating record', [
                'service' => static::class,
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int|string $id): bool
    {
        DB::beginTransaction();
        
        try {
            // Get existing model
            $existingModel = $this->repository->findOrFail($id);
            
            // Hook: Before delete
            $this->beforeDelete($existingModel);
            
            // Delete the record
            $result = $this->repository->delete($id);
            
            // Hook: After delete
            $this->afterDelete($existingModel);
            
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting record', [
                'service' => static::class,
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function bulkDelete(array $ids): int
    {
        DB::beginTransaction();
        
        try {
            // Hook: Before bulk delete
            $this->beforeBulkDelete($ids);
            
            // Delete records
            $count = $this->repository->bulkDelete($ids);
            
            // Hook: After bulk delete
            $this->afterBulkDelete($ids, $count);
            
            DB::commit();
            
            return $count;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error bulk deleting records', [
                'service' => static::class,
                'ids' => $ids,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Hook: Execute logic before create
     *
     * @param array $data
     * @return array Modified data
     */
    protected function beforeCreate(array $data): array
    {
        return $data;
    }

    /**
     * Hook: Execute logic after create
     *
     * @param Model $model
     * @param array $data
     * @return void
     */
    protected function afterCreate(Model $model, array $data): void
    {
        // Override in child classes if needed
    }

    /**
     * Hook: Execute logic before update
     *
     * @param Model $existingModel
     * @param array $data
     * @return array Modified data
     */
    protected function beforeUpdate(Model $existingModel, array $data): array
    {
        return $data;
    }

    /**
     * Hook: Execute logic after update
     *
     * @param Model $model Updated model
     * @param Model $existingModel Original model
     * @param array $data
     * @return void
     */
    protected function afterUpdate(Model $model, Model $existingModel, array $data): void
    {
        // Override in child classes if needed
    }

    /**
     * Hook: Execute logic before delete
     *
     * @param Model $model
     * @return void
     */
    protected function beforeDelete(Model $model): void
    {
        // Override in child classes if needed
    }

    /**
     * Hook: Execute logic after delete
     *
     * @param Model $model
     * @return void
     */
    protected function afterDelete(Model $model): void
    {
        // Override in child classes if needed
    }

    /**
     * Hook: Execute logic before bulk delete
     *
     * @param array $ids
     * @return void
     */
    protected function beforeBulkDelete(array $ids): void
    {
        // Override in child classes if needed
    }

    /**
     * Hook: Execute logic after bulk delete
     *
     * @param array $ids
     * @param int $count Number of deleted records
     * @return void
     */
    protected function afterBulkDelete(array $ids, int $count): void
    {
        // Override in child classes if needed
    }

    /**
     * Get the repository instance
     *
     * @return RepositoryInterface
     */
    protected function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }
}
