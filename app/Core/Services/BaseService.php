<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Contracts\ServiceInterface;
use App\Core\Contracts\RepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Base Service Abstract Class
 * 
 * Provides common service layer implementations
 * All module services should extend this class
 */
abstract class BaseService implements ServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * BaseService constructor
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
    public function getAll(array $filters = []): mixed
    {
        if (isset($filters['paginate']) && $filters['paginate']) {
            $perPage = $filters['per_page'] ?? 15;
            return $this->repository->paginate($perPage);
        }

        return $this->repository->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getById(int $id): mixed
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): mixed
    {
        try {
            DB::beginTransaction();

            $record = $this->repository->create($data);

            DB::commit();

            Log::info('Record created', [
                'model' => get_class($record),
                'id' => $record->id,
            ]);

            return $record;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create record', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data): mixed
    {
        try {
            DB::beginTransaction();

            $record = $this->repository->update($id, $data);

            DB::commit();

            Log::info('Record updated', [
                'model' => get_class($record),
                'id' => $record->id,
            ]);

            return $record;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update record', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        try {
            DB::beginTransaction();

            $result = $this->repository->delete($id);

            DB::commit();

            Log::info('Record deleted', [
                'id' => $id,
            ]);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete record', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);

            throw $e;
        }
    }
}
