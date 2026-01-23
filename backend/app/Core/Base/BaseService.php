<?php

namespace App\Core\Base;

use App\Core\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Base Service Class
 * Implements business logic layer with transaction management
 */
abstract class BaseService
{
    protected RepositoryInterface $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all records
     */
    public function getAll(array $columns = ['*']): Collection
    {
        return $this->repository->all($columns);
    }

    /**
     * Get paginated records
     */
    public function getPaginated(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $columns);
    }

    /**
     * Find record by ID
     */
    public function findById(int $id, array $columns = ['*']): ?Model
    {
        return $this->repository->find($id, $columns);
    }

    /**
     * Find record by ID or fail
     */
    public function findByIdOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->repository->findOrFail($id, $columns);
    }

    /**
     * Create a new record with transaction support
     */
    public function create(array $data): Model
    {
        try {
            DB::beginTransaction();
            
            $record = $this->repository->create($data);
            $this->afterCreate($record, $data);
            
            DB::commit();
            
            Log::info(static::class . ' created', ['id' => $record->id]);
            
            return $record;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(static::class . ' creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update a record with transaction support
     */
    public function update(int $id, array $data): Model
    {
        try {
            DB::beginTransaction();
            
            $record = $this->repository->update($id, $data);
            $this->afterUpdate($record, $data);
            
            DB::commit();
            
            Log::info(static::class . ' updated', ['id' => $id]);
            
            return $record;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(static::class . ' update failed', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Delete a record with transaction support
     */
    public function delete(int $id): bool
    {
        try {
            DB::beginTransaction();
            
            $this->beforeDelete($id);
            $result = $this->repository->delete($id);
            $this->afterDelete($id);
            
            DB::commit();
            
            Log::info(static::class . ' deleted', ['id' => $id]);
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(static::class . ' deletion failed', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * Hook: After record creation
     */
    protected function afterCreate(Model $record, array $data): void
    {
        // Override in child classes if needed
    }

    /**
     * Hook: After record update
     */
    protected function afterUpdate(Model $record, array $data): void
    {
        // Override in child classes if needed
    }

    /**
     * Hook: Before record deletion
     */
    protected function beforeDelete(int $id): void
    {
        // Override in child classes if needed
    }

    /**
     * Hook: After record deletion
     */
    protected function afterDelete(int $id): void
    {
        // Override in child classes if needed
    }
}
