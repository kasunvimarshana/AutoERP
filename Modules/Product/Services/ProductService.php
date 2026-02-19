<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Product\Enums\ProductType;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Events\ProductUpdated;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductBundle;
use Modules\Product\Models\ProductComposite;
use Modules\Product\Repositories\ProductRepository;

/**
 * Product Service
 *
 * Business logic layer for product management including CRUD operations,
 * bundle/composite management, and code generation.
 * Follows Clean Architecture with controller → service → repository separation.
 */
class ProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {}

    /**
     * Get paginated products with optional filtering.
     */
    public function getPaginatedProducts(
        ?ProductType $type = null,
        ?string $categoryId = null,
        ?bool $isActive = null,
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Product::query()
            ->with(['category', 'buyingUnit', 'sellingUnit']);

        if ($type !== null) {
            $query->where('type', $type);
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        if ($search !== null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new product.
     */
    public function createProduct(array $data, string $tenantId): Product
    {
        if (empty($data['code'])) {
            $data['code'] = $this->generateProductCode();
        }

        $data['tenant_id'] = $tenantId;
        $data['is_active'] = $data['is_active'] ?? true;

        $product = TransactionHelper::runInTransaction(function () use ($data) {
            $product = $this->productRepository->create($data);
            event(new ProductCreated($product));

            return $product;
        });

        $product->load(['category', 'buyingUnit', 'sellingUnit']);

        return $product;
    }

    /**
     * Get a product by ID with relationships.
     */
    public function getProductById(string $id, array $with = []): Product
    {
        $defaultRelations = [
            'category',
            'buyingUnit',
            'sellingUnit',
            'bundleItems.product',
            'compositeParts.component',
        ];

        $relations = empty($with) ? $defaultRelations : $with;

        $product = $this->productRepository->findOrFail($id);
        $product->load($relations);

        return $product;
    }

    /**
     * Update a product.
     */
    public function updateProduct(Product $product, array $data): Product
    {
        $product = TransactionHelper::runInTransaction(function () use ($product, $data) {
            $updated = $this->productRepository->update($product->id, $data);
            event(new ProductUpdated($updated));

            return $updated;
        });

        $product->load(['category', 'buyingUnit', 'sellingUnit']);

        return $product;
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(Product $product): bool
    {
        return TransactionHelper::runInTransaction(function () use ($product) {
            return $this->productRepository->delete($product->id);
        });
    }

    /**
     * Get bundle items for a product.
     */
    public function getBundleItems(Product $product): Collection
    {
        if ($product->type !== ProductType::BUNDLE) {
            throw new \InvalidArgumentException('Product must be of type BUNDLE.');
        }

        return $product->bundleItems()->with('product')->get();
    }

    /**
     * Add a bundle item to a product.
     */
    public function addBundleItem(Product $product, array $data, string $tenantId): ProductBundle
    {
        if ($product->type !== ProductType::BUNDLE) {
            throw new \InvalidArgumentException('Product must be of type BUNDLE.');
        }

        $data['tenant_id'] = $tenantId;
        $data['bundle_id'] = $product->id;

        $bundleItem = TransactionHelper::runInTransaction(function () use ($data) {
            return ProductBundle::create($data);
        });

        $bundleItem->load('product');

        return $bundleItem;
    }

    /**
     * Remove a bundle item from a product.
     */
    public function removeBundleItem(Product $product, ProductBundle $bundleItem): bool
    {
        if ($bundleItem->bundle_id !== $product->id) {
            throw new \InvalidArgumentException('Bundle item does not belong to this product.');
        }

        return TransactionHelper::runInTransaction(function () use ($bundleItem) {
            return $bundleItem->delete();
        });
    }

    /**
     * Get composite parts for a product.
     */
    public function getCompositeParts(Product $product): Collection
    {
        if ($product->type !== ProductType::COMPOSITE) {
            throw new \InvalidArgumentException('Product must be of type COMPOSITE.');
        }

        return $product->compositeParts()
            ->with('component')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Add a composite part to a product.
     */
    public function addCompositePart(Product $product, array $data, string $tenantId): ProductComposite
    {
        if ($product->type !== ProductType::COMPOSITE) {
            throw new \InvalidArgumentException('Product must be of type COMPOSITE.');
        }

        $data['tenant_id'] = $tenantId;
        $data['composite_id'] = $product->id;

        if (! isset($data['sort_order'])) {
            $maxOrder = $product->compositeParts()->max('sort_order') ?? 0;
            $data['sort_order'] = $maxOrder + 1;
        }

        $compositePart = TransactionHelper::runInTransaction(function () use ($data) {
            return ProductComposite::create($data);
        });

        $compositePart->load('component');

        return $compositePart;
    }

    /**
     * Remove a composite part from a product.
     */
    public function removeCompositePart(Product $product, ProductComposite $compositePart): bool
    {
        if ($compositePart->composite_id !== $product->id) {
            throw new \InvalidArgumentException('Composite part does not belong to this product.');
        }

        return TransactionHelper::runInTransaction(function () use ($compositePart) {
            return $compositePart->delete();
        });
    }

    /**
     * Find products by category.
     */
    public function findByCategory(string $categoryId, bool $includeInactive = false): Collection
    {
        return $this->productRepository->findByCategory($categoryId, $includeInactive);
    }

    /**
     * Find products by type.
     */
    public function findByType(ProductType $type, bool $includeInactive = false): Collection
    {
        return $this->productRepository->findByType($type, $includeInactive);
    }

    /**
     * Search products by name or code.
     */
    public function searchProducts(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->search($query, $perPage);
    }

    /**
     * Get active products only.
     */
    public function getActiveProducts(): Collection
    {
        return $this->productRepository->getActiveProducts();
    }

    /**
     * Generate a unique product code.
     *
     * Note: Uses optimistic approach with random generation and existence check.
     * Database unique constraint on 'code' column provides final protection against
     * race conditions. Consider using a sequence-based approach for extremely high
     * concurrency scenarios.
     */
    private function generateProductCode(): string
    {
        $prefix = config('product.code.prefix', 'PRD');
        $length = config('product.code.length', 8);
        $numberLength = $length - strlen($prefix);
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $number = str_pad((string) random_int(0, (10 ** $numberLength) - 1), $numberLength, '0', STR_PAD_LEFT);
            $code = $prefix.$number;
            $exists = Product::where('code', $code)->exists();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException('Failed to generate unique product code after '.$maxAttempts.' attempts');
            }
        } while ($exists);

        return $code;
    }
}
