<?php

namespace App\Services;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Base Service Class
 * 
 * Abstract base class for all service layer implementations.
 * Handles business logic orchestration, transaction management,
 * and exception handling. Services are the only layer that should
 * manage database transactions.
 */
abstract class BaseService
{
    /**
     * @var BaseRepositoryInterface
     */
    protected BaseRepositoryInterface $repository;

    /**
     * Constructor
     *
     * @param BaseRepositoryInterface $repository
     */
    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all records with optional filters
     *
     * @param array $filters
     * @return Collection
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->repository->all($filters);
    }

    /**
     * Get paginated records
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters);
    }

    /**
     * Get a record by ID
     *
     * @param int $id
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id): Model
    {
        $model = $this->repository->find($id);
        
        if (!$model) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                'Record not found with ID: ' . $id
            );
        }
        
        return $model;
    }

    /**
     * Create a new record
     * 
     * Wrapped in a database transaction to ensure atomicity.
     * Child classes should override this method to add custom logic.
     *
     * @param array $data
     * @return Model
     * @throws \Exception
     */
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Validate data before creation (child classes can override validateCreate)
            $this->validateCreate($data);
            
            // Perform any pre-creation logic
            $data = $this->beforeCreate($data);
            
            // Create the record
            $model = $this->repository->create($data);
            
            // Perform any post-creation logic
            $this->afterCreate($model, $data);
            
            // Log the creation
            Log::info('Record created', [
                'model' => get_class($model),
                'id' => $model->id,
            ]);
            
            return $model;
        });
    }

    /**
     * Update an existing record
     * 
     * Wrapped in a database transaction to ensure atomicity.
     * Child classes should override this method to add custom logic.
     *
     * @param int $id
     * @param array $data
     * @return Model
     * @throws \Exception
     */
    public function update(int $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data) {
            // Get the existing model
            $model = $this->getById($id);
            
            // Validate data before update
            $this->validateUpdate($model, $data);
            
            // Perform any pre-update logic
            $data = $this->beforeUpdate($model, $data);
            
            // Update the record
            $updatedModel = $this->repository->update($model, $data);
            
            // Perform any post-update logic
            $this->afterUpdate($updatedModel, $data);
            
            // Log the update
            Log::info('Record updated', [
                'model' => get_class($updatedModel),
                'id' => $updatedModel->id,
            ]);
            
            return $updatedModel;
        });
    }

    /**
     * Delete a record
     * 
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            // Get the existing model
            $model = $this->getById($id);
            
            // Validate before deletion
            $this->validateDelete($model);
            
            // Perform any pre-deletion logic
            $this->beforeDelete($model);
            
            // Delete the record
            $result = $this->repository->delete($model);
            
            // Perform any post-deletion logic
            $this->afterDelete($model);
            
            // Log the deletion
            Log::info('Record deleted', [
                'model' => get_class($model),
                'id' => $id,
            ]);
            
            return $result;
        });
    }

    /**
     * Validate data before create
     * Override in child classes for custom validation
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function validateCreate(array $data): void
    {
        // Override in child classes
    }

    /**
     * Validate data before update
     * Override in child classes for custom validation
     *
     * @param Model $model
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function validateUpdate(Model $model, array $data): void
    {
        // Override in child classes
    }

    /**
     * Validate before delete
     * Override in child classes for custom validation
     *
     * @param Model $model
     * @return void
     * @throws \Exception
     */
    protected function validateDelete(Model $model): void
    {
        // Override in child classes
    }

    /**
     * Hook: Before create
     * Override in child classes for custom logic
     *
     * @param array $data
     * @return array Modified data
     */
    protected function beforeCreate(array $data): array
    {
        return $data;
    }

    /**
     * Hook: After create
     * Override in child classes for custom logic (e.g., fire events)
     *
     * @param Model $model
     * @param array $data
     * @return void
     */
    protected function afterCreate(Model $model, array $data): void
    {
        // Override in child classes
    }

    /**
     * Hook: Before update
     * Override in child classes for custom logic
     *
     * @param Model $model
     * @param array $data
     * @return array Modified data
     */
    protected function beforeUpdate(Model $model, array $data): array
    {
        return $data;
    }

    /**
     * Hook: After update
     * Override in child classes for custom logic (e.g., fire events)
     *
     * @param Model $model
     * @param array $data
     * @return void
     */
    protected function afterUpdate(Model $model, array $data): void
    {
        // Override in child classes
    }

    /**
     * Hook: Before delete
     * Override in child classes for custom logic
     *
     * @param Model $model
     * @return void
     */
    protected function beforeDelete(Model $model): void
    {
        // Override in child classes
    }

    /**
     * Hook: After delete
     * Override in child classes for custom logic (e.g., fire events)
     *
     * @param Model $model
     * @return void
     */
    protected function afterDelete(Model $model): void
    {
        // Override in child classes
    }
}
