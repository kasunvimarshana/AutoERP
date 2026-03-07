<?php

namespace App\Services;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    public function __construct(protected BaseRepositoryInterface $repository)
    {
    }

    public function getAll(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->repository->all($columns, $relations);
    }

    public function findById(int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->repository->find($id, $columns, $relations);
    }

    public function findBy(string $field, mixed $value, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->repository->findBy($field, $value, $columns, $relations);
    }

    public function create(array $data): Model
    {
        return $this->repository->transaction(fn () => $this->repository->create($data));
    }

    public function update(int $id, array $data): Model
    {
        return $this->repository->transaction(fn () => $this->repository->update($id, $data));
    }

    public function delete(int $id): bool
    {
        return $this->repository->transaction(fn () => $this->repository->delete($id));
    }

    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        array $relations = [],
        array $filters = [],
        string $sortBy = 'id',
        string $sortDirection = 'asc',
        string $search = ''
    ): LengthAwarePaginator {
        return $this->repository->paginate(
            $perPage,
            $columns,
            $relations,
            $filters,
            $sortBy,
            $sortDirection,
            $search
        );
    }

    public function exists(array $conditions): bool
    {
        return $this->repository->exists($conditions);
    }

    public function count(array $conditions = []): int
    {
        return $this->repository->count($conditions);
    }

    public function getRepository(): BaseRepositoryInterface
    {
        return $this->repository;
    }
}
