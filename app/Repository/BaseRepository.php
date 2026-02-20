<?php

namespace App\Repository;

use App\Contracts\Repository\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseRepository implements RepositoryInterface
{
    public function __construct(protected readonly Model $model) {}

    public function findById(int|string $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function findAll(array $filters = [], array $orderBy = [], int $limit = 0): Collection
    {
        $query = $this->model->newQuery();

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        foreach ($orderBy as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int|string $id, array $data): Model
    {
        $record = $this->model->newQuery()->findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }
}
