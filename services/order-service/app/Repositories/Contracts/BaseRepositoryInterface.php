<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']): Collection;

    public function find(int|string $id, array $columns = ['*']): ?Model;

    public function findBy(string $column, mixed $value, array $columns = ['*']): Collection;

    public function create(array $data): Model;

    public function update(int|string $id, array $data): Model;

    public function delete(int|string $id): bool;

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    public function search(string $term, array $columns): Collection;

    public function filter(array $filters): Collection;

    public function sort(string $column, string $direction = 'asc'): Collection;

    public function paginateConditional(Builder $query, Request $request): Collection|LengthAwarePaginator;

    public function withRelations(array $relations): static;

    public function crossServiceFetch(string $serviceUrl, string $endpoint, array $params = []): mixed;
}
