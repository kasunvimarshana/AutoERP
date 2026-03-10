<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * BaseRepositoryInterface
 *
 * Service-local copy of the shared contract so each microservice can be
 * independently deployed without a shared package manager dependency.
 *
 * @see \KvSaas\Contracts\Interfaces\BaseRepositoryInterface
 */
interface BaseRepositoryInterface
{
    public function all(array $filters = [], array $columns = ['*'], array $relations = [], array $orderBy = []): Collection;

    public function paginate(array $filters = [], int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null, array $relations = [], array $orderBy = []): LengthAwarePaginator;

    public function find(int|string $id, array $columns = ['*'], array $relations = []): ?Model;

    public function findOrFail(int|string $id, array $columns = ['*'], array $relations = []): Model;

    public function findBy(array $criteria, array $columns = ['*'], array $relations = []): ?Model;

    public function findAllBy(array $criteria, array $columns = ['*'], array $relations = [], array $orderBy = []): Collection;

    public function create(array $data): Model;

    public function update(int|string $id, array $data): Model;

    public function updateOrCreate(array $attributes, array $values = []): Model;

    public function delete(int|string $id): bool;

    public function softDelete(int|string $id): bool;

    public function restore(int|string $id): bool;

    public function count(array $criteria = []): int;

    public function bulkInsert(array $data): bool;

    public function search(string $term, array $columns, array $filters = [], int $perPage = 15, array $relations = []): LengthAwarePaginator;

    public function rawQuery(string $query, array $bindings = []): Collection;

    public function loadRelations(Model|Collection $resource, array $relations): Model|Collection;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;
}
