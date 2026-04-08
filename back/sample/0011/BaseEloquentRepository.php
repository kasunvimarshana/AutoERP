<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Domain\RepositoryInterfaces\BaseRepositoryInterface;

/**
 * BaseEloquentRepository
 *
 * Provides default Eloquent implementations for the common CRUD contract.
 * Module repositories extend this and override as needed.
 *
 * @template TModel of Model
 * @template TEntity
 */
abstract class BaseEloquentRepository implements BaseRepositoryInterface
{
    public function __construct(
        protected readonly Model $model,
    ) {}

    public function findById(int $id): mixed
    {
        $model = $this->model->newQuery()->find($id);
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findAll(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function existsById(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->delete();
    }

    /**
     * Convert Eloquent model to domain entity.
     * Must be implemented by each module repository.
     */
    abstract protected function toDomainEntity(Model $model): mixed;

    /**
     * Convert domain entity to Eloquent model attributes.
     * Must be implemented by each module repository.
     */
    abstract protected function toModelAttributes(mixed $entity): array;

    /**
     * Apply filters to query builder.
     * Override in subclass for module-specific filter logic.
     */
    protected function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
    }
}
