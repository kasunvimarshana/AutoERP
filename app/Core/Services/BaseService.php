<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Contracts\RepositoryInterface;
use App\Core\Contracts\ServiceInterface;
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
    protected RepositoryInterface $repository;

    /**
     * BaseService constructor
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
        // Check if we're already in a transaction (e.g., from orchestrator or test)
        // In test environment, let RefreshDatabase manage transactions
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $record = $this->repository->create($data);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            Log::info('Record created', [
                'model' => get_class($record),
                'id' => $record->id,
            ]);

            return $record;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }

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
        // Check if we're already in a transaction (e.g., from orchestrator or test)
        // In test environment, let RefreshDatabase manage transactions
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $record = $this->repository->update($id, $data);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            Log::info('Record updated', [
                'model' => get_class($record),
                'id' => $record->id,
            ]);

            return $record;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }

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
        // Check if we're already in a transaction (e.g., from orchestrator or test)
        // In test environment, let RefreshDatabase manage transactions
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $result = $this->repository->delete($id);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            Log::info('Record deleted', [
                'id' => $id,
            ]);

            return $result;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }

            Log::error('Failed to delete record', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);

            throw $e;
        }
    }
}
