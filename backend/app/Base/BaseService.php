<?php

namespace App\Base;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

abstract class BaseService
{
    protected BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(array $relations = []): Collection
    {
        try {
            return $this->repository->all(['*'], $relations);
        } catch (Exception $e) {
            Log::error('Error in ' . static::class . '::getAll: ' . $e->getMessage());
            throw $e;
        }
    }

    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        try {
            return $this->repository->paginate($perPage, ['*'], $relations);
        } catch (Exception $e) {
            Log::error('Error in ' . static::class . '::paginate: ' . $e->getMessage());
            throw $e;
        }
    }

    public function findById(string $id, array $relations = []): ?Model
    {
        try {
            return $this->repository->find($id, ['*'], $relations);
        } catch (Exception $e) {
            Log::error('Error in ' . static::class . '::findById: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $data): Model
    {
        DB::beginTransaction();
        
        try {
            $result = $this->repository->create($data);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ' . static::class . '::create: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(string $id, array $data): bool
    {
        DB::beginTransaction();
        
        try {
            $result = $this->repository->update($id, $data);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ' . static::class . '::update: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(string $id): bool
    {
        DB::beginTransaction();
        
        try {
            $result = $this->repository->delete($id);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ' . static::class . '::delete: ' . $e->getMessage());
            throw $e;
        }
    }

    public function restore(string $id): bool
    {
        DB::beginTransaction();
        
        try {
            $result = $this->repository->restore($id);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ' . static::class . '::restore: ' . $e->getMessage());
            throw $e;
        }
    }

    public function forceDelete(string $id): bool
    {
        DB::beginTransaction();
        
        try {
            $result = $this->repository->forceDelete($id);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ' . static::class . '::forceDelete: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function executeInTransaction(callable $callback)
    {
        DB::beginTransaction();
        
        try {
            $result = $callback();
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Transaction error in ' . static::class . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
