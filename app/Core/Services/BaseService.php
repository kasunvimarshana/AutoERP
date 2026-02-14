<?php

namespace App\Core\Services;

use App\Core\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    protected BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            try {
                $record = $this->repository->create($data);
                $this->afterCreate($record, $data);
                return $record;
            } catch (\Exception $e) {
                Log::error('Service create failed', [
                    'service' => get_class($this),
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            try {
                $record = $this->repository->update($id, $data);
                $this->afterUpdate($record, $data);
                return $record;
            } catch (\Exception $e) {
                Log::error('Service update failed', [
                    'service' => get_class($this),
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    public function delete($id): bool
    {
        return DB::transaction(function () use ($id) {
            try {
                $result = $this->repository->delete($id);
                $this->afterDelete($id);
                return $result;
            } catch (\Exception $e) {
                Log::error('Service delete failed', [
                    'service' => get_class($this),
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function all()
    {
        return $this->repository->all();
    }

    public function paginate(int $perPage = 15, array $filters = [])
    {
        return $this->repository->paginate($perPage, $filters);
    }

    protected function afterCreate($record, array $data): void
    {
    }

    protected function afterUpdate($record, array $data): void
    {
    }

    protected function afterDelete($id): void
    {
    }
}
