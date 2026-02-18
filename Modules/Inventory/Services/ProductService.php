<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\Inventory\Entities\Product;
use Modules\Inventory\Repositories\Contracts\ProductRepositoryInterface;

/**
 * Product Service
 *
 * Handles business logic for product management operations.
 */
class ProductService extends BaseService
{
    /**
     * ProductService constructor.
     */
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Get paginated products.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($perPage);
    }

    /**
     * Create a new product.
     */
    public function create(array $data): Product
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSku();
            }

            $product = $this->productRepository->create($data);

            $this->logInfo('Product created', [
                'product_id' => $product->id,
                'sku' => $product->sku,
            ]);

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function update(int $id, array $data): Product
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $product = $this->productRepository->update($id, $data);

            $this->logInfo('Product updated', [
                'product_id' => $product->id,
            ]);

            return $product;
        });
    }

    /**
     * Delete a product.
     */
    public function delete(int $id): bool
    {
        $result = $this->productRepository->delete($id);

        if ($result) {
            $this->logInfo('Product deleted', [
                'product_id' => $id,
            ]);
        }

        return $result;
    }

    /**
     * Find product by ID.
     */
    public function findById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }

    /**
     * Find product by SKU.
     */
    public function findBySku(string $sku): ?Product
    {
        return $this->productRepository->findBySku($sku);
    }

    /**
     * Find product by barcode.
     */
    public function findByBarcode(string $barcode): ?Product
    {
        return $this->productRepository->findByBarcode($barcode);
    }

    /**
     * Get active products.
     */
    public function getActiveProducts(): Collection
    {
        return $this->productRepository->getActiveProducts();
    }

    /**
     * Get products by category.
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->productRepository->getByCategory($categoryId);
    }

    /**
     * Search products.
     */
    public function search(string $query): Collection
    {
        return $this->productRepository->search($query);
    }

    /**
     * Get products needing reorder.
     */
    public function getProductsNeedingReorder(): Collection
    {
        return $this->productRepository->getProductsNeedingReorder();
    }

    /**
     * Generate a unique SKU.
     */
    protected function generateSku(): string
    {
        $prefix = 'PRD';
        $year = date('Y');

        $lastProduct = $this->productRepository
            ->getModel()
            ->where('sku', 'LIKE', "{$prefix}-{$year}-%")
            ->orderBy('sku', 'desc')
            ->first();

        if ($lastProduct) {
            $parts = explode('-', $lastProduct->sku);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }
}
