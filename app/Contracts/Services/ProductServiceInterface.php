<?php

namespace App\Contracts\Services;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductServiceInterface
{
    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Product;

    public function update(string $id, array $data): Product;

    public function delete(string $id): void;
}
