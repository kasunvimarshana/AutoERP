<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService extends BaseService
{
    protected ProductRepository $productRepository;

    /**
     * ProductService constructor.
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
        parent::__construct($productRepository);
    }

    /**
     * Create product with auto-generated slug and SKU if not provided
     */
    public function createProduct(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSku($data);
            }

            // Ensure tenant_id is set
            if (empty($data['tenant_id']) && auth()->check()) {
                $data['tenant_id'] = auth()->user()->tenant_id;
            }

            $product = $this->productRepository->create($data);

            return [
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product,
            ];
        });
    }

    /**
     * Update product
     */
    public function updateProduct(int $id, array $data): array
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            // Update slug if name changed
            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $product = $this->productRepository->update($id, $data);

            return [
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product,
            ];
        });
    }

    /**
     * Delete product (soft delete)
     */
    public function deleteProduct(int $id): array
    {
        return $this->executeInTransaction(function () use ($id) {
            $product = $this->productRepository->findById($id);

            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Product not found',
                ];
            }

            // Check if product has stock
            if ($product->getStockBalance() > 0) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete product with existing stock. Please clear stock first.',
                ];
            }

            $this->productRepository->delete($id);

            return [
                'success' => true,
                'message' => 'Product deleted successfully',
            ];
        });
    }

    /**
     * Get product by SKU
     */
    public function getProductBySku(string $sku): ?object
    {
        return $this->productRepository->findBySku($sku);
    }

    /**
     * Get product by barcode
     */
    public function getProductByBarcode(string $barcode): ?object
    {
        return $this->productRepository->findByBarcode($barcode);
    }

    /**
     * Search products
     */
    public function searchProducts(string $term, array $filters = []): array
    {
        $products = $this->productRepository->search($term, $filters, ['variants', 'batches']);

        return [
            'success' => true,
            'data' => $products,
            'count' => $products->count(),
        ];
    }

    /**
     * Get products with low stock
     */
    public function getLowStockProducts(?int $branchId = null): array
    {
        $products = $this->productRepository->getLowStock($branchId);

        return [
            'success' => true,
            'data' => $products,
            'count' => $products->count(),
        ];
    }

    /**
     * Get products needing reorder
     */
    public function getProductsNeedingReorder(?int $branchId = null): array
    {
        $products = $this->productRepository->getNeedingReorder($branchId);

        return [
            'success' => true,
            'data' => $products,
            'count' => $products->count(),
        ];
    }

    /**
     * Activate/deactivate product
     */
    public function toggleProductStatus(int $id): array
    {
        return $this->executeInTransaction(function () use ($id) {
            $product = $this->productRepository->findById($id);

            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Product not found',
                ];
            }

            $newStatus = !$product->is_active;
            $this->productRepository->update($id, ['is_active' => $newStatus]);

            return [
                'success' => true,
                'message' => 'Product status updated successfully',
                'is_active' => $newStatus,
            ];
        });
    }

    /**
     * Generate unique SKU for product
     */
    protected function generateSku(array $data): string
    {
        $prefix = strtoupper(substr($data['name'] ?? 'PRD', 0, 3));
        $timestamp = now()->format('ymd');
        $random = strtoupper(Str::random(4));

        $sku = "{$prefix}-{$timestamp}-{$random}";

        // Check if SKU exists, regenerate if it does
        $attempts = 0;
        while ($this->productRepository->findBySku($sku) && $attempts < 5) {
            $random = strtoupper(Str::random(4));
            $sku = "{$prefix}-{$timestamp}-{$random}";
            $attempts++;
        }

        return $sku;
    }

    /**
     * Bulk import products from array
     */
    public function bulkImportProducts(array $products): array
    {
        return $this->executeInTransaction(function () use ($products) {
            $imported = [];
            $failed = [];

            foreach ($products as $index => $productData) {
                try {
                    $result = $this->createProduct($productData);
                    if ($result['success']) {
                        $imported[] = $result['data'];
                    } else {
                        $failed[] = [
                            'index' => $index,
                            'data' => $productData,
                            'error' => $result['message'] ?? 'Unknown error',
                        ];
                    }
                } catch (\Exception $e) {
                    $failed[] = [
                        'index' => $index,
                        'data' => $productData,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'success' => true,
                'imported' => count($imported),
                'failed' => count($failed),
                'imported_products' => $imported,
                'failed_products' => $failed,
            ];
        });
    }
}
