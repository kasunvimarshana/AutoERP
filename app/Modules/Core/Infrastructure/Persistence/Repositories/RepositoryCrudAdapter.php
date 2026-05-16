<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Repositories;

use Modules\Core\Domain\Contracts\Repositories\CrudRepositoryInterface;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

final class RepositoryCrudAdapter implements CrudRepositoryInterface
{
    public function __construct(
        private readonly RepositoryInterface $repository,
    ) {}

    public function create(array $attributes): mixed
    {
        return $this->repository->create($attributes);
    }

    public function updateById(int|string $id, array $attributes): mixed
    {
        return $this->repository->update($id, $attributes);
    }

    public function findById(int|string $id): mixed
    {
        return $this->repository->find($id);
    }

    public function paginate(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): mixed {
        $repo = $this->repository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (is_string($value) && str_contains($value, '%')) {
                $repo->where($field, 'like', $value);
            } else {
                $repo->where($field, $value);
            }
        }

        if ($sort !== null && $sort !== '') {
            if (str_starts_with($sort, '-')) {
                $column = substr($sort, 1);
                $direction = 'desc';
            } else {
                $parts = explode(':', $sort, 2);
                $column = $parts[0];
                $direction = $parts[1] ?? 'asc';
            }

            $repo->orderBy($column, strtolower($direction) === 'desc' ? 'desc' : 'asc');
        }

        if ($include !== null && $include !== '') {
            $relations = array_values(array_filter(array_map('trim', explode(',', $include))));
            if ($relations !== []) {
                $repo->with($relations);
            }
        }

        return $repo->paginate($perPage, ['*'], config('core.pagination.page_name', 'page'), $page);
    }

    public function deleteById(int|string $id): bool
    {
        return $this->repository->delete($id);
    }
}
