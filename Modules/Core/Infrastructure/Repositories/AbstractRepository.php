<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Abstract base repository implementation.
 *
 * Provides tenant-aware CRUD operations.
 * All module repositories must extend this class and bind via the service container.
 *
 * @template TModel of Model
 */
abstract class AbstractRepository implements RepositoryContract
{
    /**
     * The Eloquent model class name.
     *
     * @var class-string<TModel>
     */
    protected string $modelClass;

    /**
     * Return a new query builder scoped to the current tenant.
     *
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->modelClass::query();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ModelNotFoundException
     */
    public function update(int|string $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ModelNotFoundException
     */
    public function delete(int|string $id): bool
    {
        $model = $this->findOrFail($id);

        return (bool) $model->delete();
    }
}
