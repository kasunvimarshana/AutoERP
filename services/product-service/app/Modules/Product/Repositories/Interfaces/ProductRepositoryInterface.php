<?php

namespace App\Modules\Product\Repositories\Interfaces;

use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function findById(int $id): ?Product;
    public function findBySku(string $sku): ?Product;
    public function create(ProductDTO $dto): Product;
    public function update(int $id, array $data): ?Product;
    public function delete(int $id): bool;
    public function getAllCategories(): array;
}
