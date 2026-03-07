<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Events\ProductCreated;
use App\Modules\Product\Events\ProductDeleted;
use App\Modules\Product\Events\ProductUpdated;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Repositories\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function listProducts(
        int $perPage = 15,
        array $filters = [],
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        return $this->productRepository->paginate($perPage, $filters, $search, $sortBy, $sortDir);
    }

    public function getProduct(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    public function createProduct(array $data): Product
    {
        $product = $this->productRepository->create($data);
        Event::dispatch(new ProductCreated($product));
        return $product;
    }

    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->productRepository->update($id, $data);
        Event::dispatch(new ProductUpdated($product));
        return $product;
    }

    public function deleteProduct(int $id): bool
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return false;
        }

        $tenantId = $product->tenant_id;
        $result = $this->productRepository->delete($id);

        if ($result) {
            Event::dispatch(new ProductDeleted($id, $tenantId));
        }

        return $result;
    }
}
