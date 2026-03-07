<?php

namespace App\Repositories\Interfaces;

use App\DTOs\ProductDTO;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

interface ProductRepositoryInterface
{
    public function queryBuilder(): QueryBuilder;

    public function findById(int $id): ?Product;

    public function findBySku(string $sku, string $tenantId): ?Product;

    public function create(ProductDTO $dto): Product;

    public function update(Product $product, ProductDTO $dto): Product;

    public function delete(Product $product): bool;

    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
