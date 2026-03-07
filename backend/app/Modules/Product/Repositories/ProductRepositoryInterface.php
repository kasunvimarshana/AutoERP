<?php

namespace App\Modules\Product\Repositories;

use App\Modules\Product\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function all(array $filters = []): Collection;

    public function find(int $id): ?Product;

    public function findBySku(string $sku): ?Product;

    public function create(array $data): Product;

    public function update(int $id, array $data): Product;

    public function delete(int $id): bool;

    public function paginate(
        int $perPage,
        array $filters,
        ?string $search,
        string $sortBy,
        string $sortDir
    ): LengthAwarePaginator;
}
