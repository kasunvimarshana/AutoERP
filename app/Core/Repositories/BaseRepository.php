<?php

namespace App\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected Model $model;

    abstract protected function model(): string;

    public function __construct()
    {
        $this->model = app($this->model());
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find($id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail($id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update($id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete($id): bool
    {
        return $this->findOrFail($id)->delete();
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $allowedFilters = $this->getFilterableColumns();
        
        foreach ($filters as $key => $value) {
            if (!empty($value) && in_array($key, $allowedFilters)) {
                $query->where($key, $value);
            }
        }

        return $query->paginate($perPage);
    }

    protected function getFilterableColumns(): array
    {
        return [];
    }

    public function where(string $column, $value): Collection
    {
        return $this->model->where($column, $value)->get();
    }
}
