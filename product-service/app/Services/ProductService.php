<?php

namespace App\Services;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProductService
 *
 * Encapsulates all business logic for product CRUD operations.
 * Wraps database operations in transactions and fires domain events
 * that are published to RabbitMQ for cross-service communication.
 *
 * Transaction / Rollback Strategy:
 * - All write operations (create, update, delete) run inside DB::transaction().
 * - Events are dispatched AFTER the transaction commits, ensuring other services
 *   only react to confirmed state changes.
 * - If any step fails, the transaction rolls back automatically.
 * - The InventoryService (Node.js) handles its own compensating actions on failure
 *   events.
 */
class ProductService
{
    /**
     * Retrieve a paginated list of all products.
     * Inventory data is enriched by the ProductController via the Inventory Service API.
     *
     * @param  array  $filters  Optional filters: category, is_active, search
     * @param  int    $perPage  Pagination size
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllProducts(array $filters = [], int $perPage = 15)
    {
        $query = Product::query();

        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Find a single product by ID.
     *
     * @param  int $id
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getProductById(int $id): Product
    {
        return Product::findOrFail($id);
    }

    /**
     * Create a new product inside a database transaction.
     * Fires ProductCreated event after commit, triggering RabbitMQ publish.
     *
     * @param  array $data  Validated product data
     * @return Product
     * @throws \Throwable
     */
    public function createProduct(array $data): Product
    {
        $product = DB::transaction(function () use ($data): Product {
            $product = Product::create($data);

            Log::info('ProductService: Product created in transaction', [
                'product_id' => $product->id,
                'name'       => $product->name,
            ]);

            return $product;
        });

        // Fire event AFTER transaction commits so consumers see consistent state
        event(new ProductCreated($product));

        return $product;
    }

    /**
     * Update an existing product inside a database transaction.
     * Fires ProductUpdated event after commit, so Inventory Service
     * can update related inventory records (e.g., rename by product_name).
     *
     * @param  int   $id
     * @param  array $data  Validated update data
     * @return Product
     * @throws \Throwable
     */
    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->getProductById($id);

        // Capture previous state before updating (used in event payload)
        $previousData = $product->only(['name', 'sku', 'price', 'stock', 'category']);

        $product = DB::transaction(function () use ($product, $data): Product {
            $product->update($data);
            $product->refresh();

            Log::info('ProductService: Product updated in transaction', [
                'product_id' => $product->id,
                'name'       => $product->name,
            ]);

            return $product;
        });

        // Fire event AFTER transaction commits
        event(new ProductUpdated($product, $previousData));

        return $product;
    }

    /**
     * Soft-delete a product inside a database transaction.
     * Fires ProductDeleted event after commit, triggering Inventory Service
     * to delete all related inventory records (cross-service cascade).
     *
     * @param  int $id
     * @return bool
     * @throws \Throwable
     */
    public function deleteProduct(int $id): bool
    {
        $product = $this->getProductById($id);

        // Capture data before deletion for the event payload
        $productId   = $product->id;
        $productName = $product->name;
        $productSku  = $product->sku;

        DB::transaction(function () use ($product): void {
            $product->delete();

            Log::info('ProductService: Product soft-deleted in transaction', [
                'product_id' => $product->id,
                'name'       => $product->name,
            ]);
        });

        // Fire event AFTER transaction commits so Inventory Service cascades
        event(new ProductDeleted($productId, $productName, $productSku));

        return true;
    }
}
