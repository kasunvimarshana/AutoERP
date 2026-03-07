<?php

namespace App\Modules\Product\Services\Interfaces;

use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductServiceInterface
{
    public function listProducts(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function getProduct(int $id): Product;
    public function createProduct(ProductDTO $dto): Product;
    public function updateProduct(int $id, array $data): Product;
    public function deleteProduct(int $id): void;
    public function getCategories(): array;
}
