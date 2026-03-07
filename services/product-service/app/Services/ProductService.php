<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Return a paginated, filtered list of products.
     *
     * @param  array<string, mixed> $filters
     */
    public function getAllProducts(array $filters): LengthAwarePaginator
    {
        return $this->productRepository->getAll($filters);
    }

    /**
     * Fetch a single product by ID.
     */
    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }

    /**
     * Create a new product, fire the ProductCreated event, and return it.
     *
     * @param  array<string, mixed> $data
     * @throws Throwable
     */
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $product = $this->productRepository->create($data);

            event(new ProductCreated($product));

            Log::info('Product created', ['product_id' => $product->id, 'sku' => $product->sku]);

            return $product;
        });
    }

    /**
     * Update an existing product, fire the ProductUpdated event, and return it.
     *
     * @param  array<string, mixed> $data
     * @throws Throwable
     */
    public function updateProduct(int $id, array $data): ?Product
    {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            return null;
        }

        return DB::transaction(function () use ($id, $data, $product): Product {
            $originalData = $product->toArray();

            $updated = $this->productRepository->update($id, $data);

            event(new ProductUpdated($updated, $originalData));

            Log::info('Product updated', ['product_id' => $id]);

            return $updated;
        });
    }

    /**
     * Delete a product and fire the ProductDeleted event.
     *
     * @throws Throwable
     */
    public function deleteProduct(int $id): bool
    {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            return false;
        }

        return DB::transaction(function () use ($id, $product): bool {
            $productData = $product->toArray();

            $deleted = $this->productRepository->delete($id);

            if ($deleted) {
                event(new ProductDeleted($id, $productData));

                Log::info('Product deleted', ['product_id' => $id]);
            }

            return $deleted;
        });
    }
}
