<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;
use Modules\Inventory\Events\ProductCreated;
use Modules\Inventory\Events\ProductUpdated;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Repositories\ProductRepository;

/**
 * Product Service
 *
 * Handles all business logic for product management.
 */
class ProductService extends BaseService
{
    /**
     * Constructor
     */
    public function __construct(
        TenantContext $tenantContext,
        protected ProductRepository $repository
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get all products with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = $this->repository->newQuery();

        // Apply filters
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('sku', 'like', "%{$filters['search']}%")
                    ->orWhere('barcode', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['product_type'])) {
            $query->where('product_type', $filters['product_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['track_inventory'])) {
            $query->where('track_inventory', $filters['track_inventory']);
        }

        // Include relationships
        $query->with(['category', 'variants', 'attributes']);

        // Sort
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create a new product.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSKU();
            }

            // Create product
            $product = $this->repository->create($data);

            // Create variants if provided
            if (isset($data['variants']) && is_array($data['variants'])) {
                foreach ($data['variants'] as $variantData) {
                    $variantData['product_id'] = $product->id;
                    $product->variants()->create($variantData);
                }
            }

            // Create custom attributes if provided
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                foreach ($data['attributes'] as $attributeData) {
                    $attributeData['product_id'] = $product->id;
                    $product->attributes()->create($attributeData);
                }
            }

            // Dispatch event
            event(new ProductCreated($product));

            // Reload relationships
            $product->load(['category', 'variants', 'attributes']);

            return $product;
        });
    }

    /**
     * Update an existing product.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->repository->findOrFail($id);

            // Update product
            $product->update($data);

            // Update variants if provided
            if (isset($data['variants']) && is_array($data['variants'])) {
                // Delete existing variants not in the update
                $variantIds = collect($data['variants'])->pluck('id')->filter();
                $product->variants()->whereNotIn('id', $variantIds)->delete();

                // Create or update variants
                foreach ($data['variants'] as $variantData) {
                    if (isset($variantData['id'])) {
                        $product->variants()->where('id', $variantData['id'])->update($variantData);
                    } else {
                        $variantData['product_id'] = $product->id;
                        $product->variants()->create($variantData);
                    }
                }
            }

            // Update attributes if provided
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                // Delete existing attributes not in the update
                $attributeIds = collect($data['attributes'])->pluck('id')->filter();
                $product->attributes()->whereNotIn('id', $attributeIds)->delete();

                // Create or update attributes
                foreach ($data['attributes'] as $attributeData) {
                    if (isset($attributeData['id'])) {
                        $product->attributes()->where('id', $attributeData['id'])->update($attributeData);
                    } else {
                        $attributeData['product_id'] = $product->id;
                        $product->attributes()->create($attributeData);
                    }
                }
            }

            // Dispatch event
            event(new ProductUpdated($product));

            // Reload relationships
            $product->load(['category', 'variants', 'attributes']);

            return $product;
        });
    }

    /**
     * Delete a product.
     */
    public function delete(string $id): bool
    {
        $product = $this->repository->findOrFail($id);

        // Check if product has stock
        if ($this->hasStock($product->id)) {
            throw new \RuntimeException('Cannot delete product with existing stock.');
        }

        return $this->repository->delete($id);
    }

    /**
     * Get product by ID with relationships.
     */
    public function getById(string $id): Product
    {
        return $this->repository->newQuery()
            ->with(['category', 'variants', 'attributes', 'stockLevels'])
            ->findOrFail($id);
    }

    /**
     * Get product by SKU.
     */
    public function getBySKU(string $sku): ?Product
    {
        return $this->repository->newQuery()
            ->where('sku', $sku)
            ->with(['category', 'variants', 'attributes'])
            ->first();
    }

    /**
     * Check if product has stock.
     */
    public function hasStock(string $productId): bool
    {
        $product = $this->repository->findOrFail($productId);

        return $product->stockLedger()->exists();
    }

    /**
     * Generate a unique SKU.
     */
    protected function generateSKU(): string
    {
        $prefix = config('inventory.sku_prefix', 'PRD');
        $lastProduct = $this->repository->newQuery()->latest('created_at')->first();

        if ($lastProduct) {
            // Extract number from last SKU and increment
            $lastNumber = (int) filter_var($lastProduct->sku, FILTER_SANITIZE_NUMBER_INT);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.'-'.str_pad((string) $newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Bulk import products from array.
     *
     * @param  array<array<string, mixed>>  $products
     * @return array{success: int, failed: int, errors: array}
     */
    public function bulkImport(array $products): array
    {
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($products as $index => $productData) {
            try {
                $this->create($productData);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'row' => $index + 1,
                    'data' => $productData,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Get low stock products.
     */
    public function getLowStockProducts(): Collection
    {
        return $this->repository->newQuery()
            ->tracksInventory()
            ->whereNotNull('reorder_level')
            ->whereHas('stockLevels', function ($query) {
                $query->whereRaw('quantity_available <= reorder_level');
            })
            ->with(['stockLevels'])
            ->get();
    }
}
