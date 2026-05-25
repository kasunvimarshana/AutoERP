<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;
use Modules\Core\Infrastructure\Persistence\Eloquent\Constants\SchemaColumns;

abstract class EloquentRepository implements RepositoryPortInterface
{
    public function __construct(
        protected Model $model,
        protected string $identifierColumn = SchemaColumns::ID,
    ) {
    }

    public function query(array $with = []): Builder
    {
        return $this->model->newQuery()->with($with);
    }

    public function findById(int|string $id, array $with = []): ?DataRecord
    {
        $model = $this->query($with)->find($id);

        return $model === null ? null : $this->toRecord($model);
    }

    public function findOrFail(int|string $id, array $with = []): DataRecord
    {
        return $this->toRecord($this->query($with)->findOrFail($id));
    }

    /**
     * @return list<DataRecord>
     */
    public function list(array $criteria = [], array $with = []): array
    {
        $models = $criteria === []
            ? $this->query($with)->get()
            : $this->applyCriteria($this->query($with), $criteria)->get();

        $records = [];
        foreach ($models as $model) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }

    public function page(
        array $criteria,
        int $perPage,
        int $page,
        array $with = [],
    ): PagedResult {
        $resolvedPage = $page > 0 ? $page : 1;
        $resolvedPerPage = $perPage > 0 ? $perPage : 1;

        $paginator = $criteria === []
            ? $this->query($with)->paginate($resolvedPerPage, ['*'], 'page', $resolvedPage)
            : $this->applyCriteria($this->query($with), $criteria)
                ->paginate($resolvedPerPage, ['*'], 'page', $resolvedPage);

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function create(array $attributes): DataRecord
    {
        return $this->toRecord($this->query()->create($attributes));
    }

    public function update(int|string $id, array $attributes): DataRecord
    {
        $targetModel = $this->resolveModel($id);
        $targetModel->fill($attributes);
        $targetModel->save();

        return $this->toRecord($targetModel);
    }

    public function delete(int|string $id): bool
    {
        $targetModel = $this->resolveModel($id);

        return (bool) $targetModel->delete();
    }

    public function restore(int|string $id): bool
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($this->model), true)) {
            return false;
        }

        $targetModel = $this->query()->withTrashed()->findOrFail($id);

        return (bool) $targetModel->restore();
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

    protected function resolveModel(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    protected function toRecord(Model $model): DataRecord
    {
        /** @var array<string, mixed> $payload */
        $payload = $model->attributesToArray();

        return new DataRecord($payload);
    }
}
