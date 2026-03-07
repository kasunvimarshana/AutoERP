<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;

class ProductService extends BaseService
{
    public function __construct(protected ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createProduct(array $data): \App\Models\Product
    {
        // Ensure SKU uniqueness within tenant
        if (!empty($data['sku']) && $this->repository->findBySku($data['sku'])) {
            throw new \InvalidArgumentException("SKU '{$data['sku']}' already exists.");
        }

        return $this->repository->transaction(fn () => $this->repository->create($data));
    }

    public function updateProduct(int $id, array $data): \App\Models\Product
    {
        $product = $this->repository->find($id);

        if (!$product) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Product not found.");
        }

        // Check SKU uniqueness excluding current product
        if (!empty($data['sku']) && $data['sku'] !== $product->sku) {
            if ($this->repository->findBySku($data['sku'])) {
                throw new \InvalidArgumentException("SKU '{$data['sku']}' already exists.");
            }
        }

        return $this->repository->transaction(fn () => $this->repository->update($id, $data));
    }

    public function getLowStockProducts(int $threshold = null): Collection
    {
        return $this->repository->getLowStockProducts($threshold);
    }

    public function findBySku(string $sku): ?\App\Models\Product
    {
        return $this->repository->findBySku($sku);
    }

    public function getProductsByCategory(int $categoryId): Collection
    {
        return $this->repository->getByCategory($categoryId);
    }
}
