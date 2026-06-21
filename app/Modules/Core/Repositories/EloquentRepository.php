<?php

declare(strict_types=1);

namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use LogicException;
use Modules\Core\Constants\SchemaColumns;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

abstract class EloquentRepository
{
    public function __construct(
        protected Model $model,
        protected string $identifierColumn = SchemaColumns::ID,
    ) {
        if (trim($this->identifierColumn) === '') {
            throw new InvalidArgumentException('Identifier column cannot be empty.');
        }
    }

    public function query(array $with = []): Builder
    {
        return $this->model->newQuery()->with($with);
    }

    public function findById(int|string $id, array $with = []): ?DataRecord
    {
        $model = $this->findByIdentifier($id, $with, false);

        return $model === null ? null : $this->toRecord($model);
    }

    public function findOrFail(int|string $id, array $with = []): DataRecord
    {
        return $this->toRecord($this->findByIdentifier($id, $with, true));
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
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than zero.');
        }

        if ($perPage < 1) {
            throw new InvalidArgumentException('Per-page must be greater than zero.');
        }

        $paginator = $criteria === []
            ? $this->query($with)->paginate($perPage, ['*'], 'page', $page)
            : $this->applyCriteria($this->query($with), $criteria)
                ->paginate($perPage, ['*'], 'page', $page);

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
            throw new LogicException(sprintf(
                'Model %s does not support restoration because it does not use SoftDeletes.',
                $this->model::class,
            ));
        }

        $targetModel = $this->findByIdentifier($id, [], true, true);

        return (bool) $targetModel->restore();
    }

    public function exists(array $criteria): bool
    {
        return $this->applyCriteria($this->query(), $criteria)->exists();
    }

    protected function applyCriteria(Builder $query, array $criteria): Builder
    {
        foreach ($criteria as $column => $value) {
            if (! is_string($column) || trim($column) === '') {
                throw new InvalidArgumentException('Criteria keys must be non-empty strings.');
            }

            if (is_array($value)) {
                if ($value === []) {
                    $query->whereRaw('1 = 0');

                    continue;
                }

                $query->whereIn($column, $value);

                continue;
            }

            if ($value === null) {
                $query->whereNull($column);

                continue;
            }

            $query->where($column, $value);
        }

        return $query;
    }

    protected function resolveModel(int|string $id): Model
    {
        return $this->findByIdentifier($id, [], true);
    }

    protected function findByIdentifier(
        int|string $id,
        array $with = [],
        bool $failIfMissing = false,
        bool $includeTrashed = false,
    ): ?Model {
        if (is_string($id) && trim($id) === '') {
            throw new InvalidArgumentException('Identifier cannot be empty.');
        }

        $query = $this->query($with);

        if ($includeTrashed) {
            $query = $query->withTrashed();
        }

        if ($this->identifierColumn === SchemaColumns::ID || $this->identifierColumn === $this->model->getKeyName()) {
            return $failIfMissing ? $query->findOrFail($id) : $query->find($id);
        }

        $query->where($this->identifierColumn, $id);

        return $failIfMissing ? $query->firstOrFail() : $query->first();
    }

    protected function toRecord(Model $model): DataRecord
    {
        /** @var array<string, mixed> $payload */
        $payload = $model->attributesToArray();

        return new DataRecord($payload);
    }
}
