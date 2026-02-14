<?php

namespace App\Core\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    public function all(): Collection;

    public function find($id): ?Model;

    public function findOrFail($id): Model;

    public function create(array $data): Model;

    public function update($id, array $data): Model;

    public function delete($id): bool;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function where(string $column, $value): Collection;
}
