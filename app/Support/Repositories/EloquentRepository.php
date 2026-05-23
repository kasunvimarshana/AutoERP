<?php

declare(strict_types=1);

namespace App\Support\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

abstract class EloquentRepository implements BaseRepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function query(array $with = []): Builder
    {
        return $this->model->newQuery()->with($with);
    }

    public function findById(int|string $id, array $with = []): ?Model
    {
        return $this->query($with)->find($id);
    }

    public function findOrFail(int|string $id, array $with = []): Model
    {
        return $this->query($with)->findOrFail($id);
    }

    public function all(array $with = []): Collection
    {
        return $this->query($with)->get();
    }

    public function getWhere(array $criteria, array $with = []): Collection
    {
        return $this->applyCriteria($this->query($with), $criteria)->get();
    }

    public function paginate(int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->query($with)->paginate($perPage);
    }

    public function paginateWhere(array $criteria, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->applyCriteria($this->query($with), $criteria)->paginate($perPage);
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(Model|int|string $model, array $attributes): Model
    {
        $model = $model instanceof Model ? $model : $this->findOrFail($model);
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    public function delete(Model|int|string $model): bool
    {
        $model = $model instanceof Model ? $model : $this->findOrFail($model);

        return (bool) $model->delete();
    }

    public function restore(Model|int|string $model): bool
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($this->model), true)) {
            return false;
        }

        $model = $model instanceof Model ? $model : $this->query()->withTrashed()->findOrFail($model);

        return (bool) $model->restore();
    }

    public function exists(array $criteria): bool
    {
        return $this->applyCriteria($this->query(), $criteria)->exists();
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    protected function applyCriteria(Builder $query, array $criteria): Builder
    {
        foreach ($criteria as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);

                continue;
            }

            $query->where($column, $value);
        }

        return $query;
    }
}
