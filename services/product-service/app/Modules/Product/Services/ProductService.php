<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\Events\ProductCreated;
use App\Modules\Product\Events\ProductDeleted;
use App\Modules\Product\Events\ProductUpdated;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Repositories\Interfaces\ProductRepositoryInterface;
use App\Modules\Product\Services\Interfaces\ProductServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function listProducts(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($filters, $perPage);
    }

    public function getProduct(int $id): Product
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            throw new ModelNotFoundException("Product not found with ID: {$id}");
        }
        return $product;
    }

    public function createProduct(ProductDTO $dto): Product
    {
        return DB::transaction(function () use ($dto) {
            $existing = $this->productRepository->findBySku($dto->sku);
            if ($existing) {
                throw new \InvalidArgumentException("Product with SKU '{$dto->sku}' already exists.");
            }

            $product = $this->productRepository->create($dto);

            Event::dispatch(new ProductCreated($product));

            return $product;
        });
    }

    public function updateProduct(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->getProduct($id);

            if (isset($data['sku']) && $data['sku'] !== $product->sku) {
                $existing = $this->productRepository->findBySku($data['sku']);
                if ($existing) {
                    throw new \InvalidArgumentException("Product with SKU '{$data['sku']}' already exists.");
                }
            }

            $updated = $this->productRepository->update($id, $data);

            Event::dispatch(new ProductUpdated($updated));

            return $updated;
        });
    }

    public function deleteProduct(int $id): void
    {
        DB::transaction(function () use ($id) {
            $product = $this->getProduct($id);
            $this->productRepository->delete($id);
            Event::dispatch(new ProductDeleted($product));
        });
    }

    public function getCategories(): array
    {
        return $this->productRepository->getAllCategories();
    }
}
