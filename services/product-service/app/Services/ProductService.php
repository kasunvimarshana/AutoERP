<?php

namespace App\Services;

use App\DTOs\ProductDTO;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\MessageBroker\Contracts\MessageBrokerInterface;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductService extends BaseService
{
    public function __construct(
        protected ProductRepositoryInterface $repository,
        private readonly MessageBrokerInterface $broker,
    ) {}

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Retrieve products with filtering, searching, sorting, and conditional pagination.
     */
    public function getProducts(Request $request, ?int $tenantId = null): Collection|LengthAwarePaginator
    {
        $query = Product::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        // Search by name or SKU
        if ($term = $request->input('search')) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('sku',  'LIKE', "%{$term}%");
            });
        }

        // Filter by category
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by price range
        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', (float) $minPrice);
        }

        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        // Filter low stock only
        if ($request->boolean('low_stock')) {
            $query->whereNotNull('reorder_point')
                  ->whereNotNull('min_stock_level')
                  ->whereColumn('min_stock_level', '<=', 'reorder_point');
        }

        // Eager load category
        if ($request->boolean('with_category', true)) {
            $query->with('category');
        }

        // Sort
        $sortColumn    = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_dir', 'desc');
        $allowedSorts  = ['name', 'sku', 'price', 'cost_price', 'created_at', 'updated_at'];

        if (in_array($sortColumn, $allowedSorts, true)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        return $this->repository->paginateConditional($query, $request);
    }

    /**
     * Get a single product by ID with its category loaded.
     */
    public function getProductById(int|string $id): ?Model
    {
        return $this->repository->withRelations(['category'])->find($id);
    }

    /**
     * Create a new product, enforcing SKU uniqueness per tenant.
     */
    public function createProduct(ProductDTO $dto): Model
    {
        $this->assertSkuUnique($dto->sku, $dto->tenantId);

        $data = $dto->toArray();

        $product = $this->repository->create($data);

        event(new ProductCreated($product));

        $this->broker->publish(
            topic:   config('services.topics.products', 'products.events'),
            payload: [
                'event'      => 'product.created',
                'product_id' => $product->id,
                'sku'        => $product->sku,
                'name'       => $product->name,
                'tenant_id'  => $product->tenant_id,
                'timestamp'  => now()->toIso8601String(),
            ],
        );

        Log::info('Product created', ['product_id' => $product->id, 'tenant_id' => $product->tenant_id]);

        return $product->load('category');
    }

    /**
     * Update an existing product, re-validating SKU uniqueness if it changed.
     */
    public function updateProduct(int|string $id, array $data): Model
    {
        $existing = $this->repository->find($id);

        if (! $existing) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Product [{$id}] not found."
            );
        }

        // Re-check SKU uniqueness only when the SKU is being changed
        if (isset($data['sku']) && $data['sku'] !== $existing->sku) {
            $this->assertSkuUnique($data['sku'], $existing->tenant_id, $id);
        }

        $product = $this->repository->update($id, $data);

        event(new ProductUpdated($product));

        Log::info('Product updated', ['product_id' => $product->id]);

        return $product->load('category');
    }

    /**
     * Soft-delete a product and notify downstream services.
     */
    public function deleteProduct(int|string $id): bool
    {
        $product = $this->repository->find($id);

        if (! $product) {
            return false;
        }

        $result = $this->repository->delete($id);

        if ($result) {
            event(new ProductDeleted($product));

            $this->broker->publish(
                topic:   config('services.topics.products', 'products.events'),
                payload: [
                    'event'      => 'product.deleted',
                    'product_id' => $product->id,
                    'sku'        => $product->sku,
                    'tenant_id'  => $product->tenant_id,
                    'timestamp'  => now()->toIso8601String(),
                ],
            );

            Log::info('Product deleted', ['product_id' => $id]);
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Cross-service helpers
    // -------------------------------------------------------------------------

    /**
     * Retrieve products by an array of IDs – used by the Inventory service.
     *
     * @param  array<int|string>  $ids
     */
    public function getProductsByIds(array $ids): Collection
    {
        return $this->repository->findByIds($ids);
    }

    // -------------------------------------------------------------------------
    // Category-scoped helpers
    // -------------------------------------------------------------------------

    public function getProductsByCategory(int|string $categoryId): Collection
    {
        return $this->repository->findByCategory($categoryId);
    }

    public function getLowStockProducts(): Collection
    {
        return $this->repository->getLowStock();
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Ensure the given SKU is unique within the tenant scope.
     *
     * @throws ValidationException
     */
    private function assertSkuUnique(string $sku, ?int $tenantId, int|string|null $excludeId = null): void
    {
        $query = Product::where('sku', $sku);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'sku' => ["The SKU '{$sku}' already exists for this tenant."],
            ]);
        }
    }
}
