<?php

declare(strict_types=1);

namespace App\Support\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function findById(int|string $id, array $with = []): ?Model;

    public function findOrFail(int|string $id, array $with = []): Model;

    public function all(array $with = []): Collection;

    public function getWhere(array $criteria, array $with = []): Collection;

    public function paginate(int $perPage = 15, array $with = []): LengthAwarePaginator;

    public function create(array $attributes): Model;

    public function update(Model|int|string $model, array $attributes): Model;

    public function delete(Model|int|string $model): bool;

    public function restore(Model|int|string $model): bool;

    public function exists(array $criteria): bool;

    public function transaction(callable $callback): mixed;
}
