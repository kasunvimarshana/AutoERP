<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Repository Interface
 */
interface BaseRepositoryInterface
{
    public function findById(string|int $id, array $relations = []): ?Model;
    public function findBy(string $field, mixed $value, array $relations = []): ?Model;
    public function getAll(array $params = []): Collection|LengthAwarePaginator;
    public function create(array $data): Model;
    public function update(string|int $id, array $data): Model;
    public function delete(string|int $id): bool;
    public function restore(string|int $id): bool;
    public function paginateData(mixed $data, array $params = []): array|LengthAwarePaginator;
}
