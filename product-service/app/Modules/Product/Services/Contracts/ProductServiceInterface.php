<?php

namespace App\Modules\Product\Services\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface ProductServiceInterface
{
    public function getAllProducts(array $filters): LengthAwarePaginator;
    public function getProductById(int $id);
    public function createProduct(array $data);
    public function updateProduct(int $id, array $data);
    public function deleteProduct(int $id): bool;
}
