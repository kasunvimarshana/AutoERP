<?php

namespace App\Services;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProductService
 *
 * All CRUD operations live here. DB transactions wrap every write so that:
 *  - the local MySQL record is only committed when the whole operation succeeds
 *  - Laravel events are dispatched *after* the transaction commits, ensuring
 *    the data is readable by the time RabbitMQ consumers react.
 *
 * Cross-service rollback strategy
 * ────────────────────────────────
 * Because Inventory Service is an independent process, we cannot include it
 * in a distributed 2-phase commit. Instead we use a compensating-transaction
 * pattern:
 *   1. Publish `product.created` → Inventory creates a stock record.
 *   2. If a subsequent operation fails, publish `product.rollback` → Inventory
 *      deletes the orphaned stock record.
 * This is the standard saga pattern for microservices.
 */
class ProductService
{
    public function __construct(
        private readonly RabbitMQPublisher     $publisher,
        private readonly InventoryServiceClient $inventoryClient,
    ) {}

    // ── List ──────────────────────────────────────────────────────────────────

    /**
     * Return all products, each enriched with its inventory from Service B.
     */
    public function list(array $filters = []): array
    {
        $query = Product::query();

        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }

        /** @var Collection $products */
        $products = $query->orderByDesc('created_at')->get();

        // Bulk-fetch inventory in one HTTP call instead of N+1 calls
        $skus        = $products->pluck('sku')->all();
        $inventoryMap = $this->inventoryClient->mapBySku($skus);

        return $products->map(fn (Product $p) => $this->merge($p, $inventoryMap))->all();
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function findOrFail(int $id): array
    {
        $product   = Product::findOrFail($id);
        $inventory = $this->inventoryClient->getByProductSku($product->sku);

        return $this->merge($product, [$product->sku => $inventory]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    /**
     * Create a product inside a DB transaction, then dispatch the domain event.
     * The event listener (queued) publishes to RabbitMQ asynchronously so that
     * a broker hiccup never rolls back the product record.
     */
    public function create(array $data): array
    {
        $product = DB::transaction(function () use ($data): Product {
            $product = Product::create($data);

            Log::info('[ProductService] Product created', ['id' => $product->id]);

            return $product;
        });

        // Dispatch AFTER commit – queued listener publishes to RabbitMQ
        event(new ProductCreated($product));

        return $this->findOrFail($product->id);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(int $id, array $data): array
    {
        $product = DB::transaction(function () use ($id, $data): Product {
            /** @var Product $product */
            $product      = Product::lockForUpdate()->findOrFail($id);
            $originalData = $product->toEventPayload(); // snapshot before change

            $product->update($data);

            // Attach the original snapshot so the event carries both states
            $product->_originalData = $originalData;

            Log::info('[ProductService] Product updated', ['id' => $product->id]);

            return $product;
        });

        event(new ProductUpdated($product, $product->_originalData));

        return $this->findOrFail($product->id);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    /**
     * Soft-delete the product, capture the payload, then publish the deletion
     * event so Inventory Service can cascade-delete its stock records.
     *
     * Rollback strategy: if the event fails to publish (broker down), the
     * product is still soft-deleted locally. A background reconciliation job
     * (not shown) can replay failed events from the `failed_jobs` table.
     */
    public function delete(int $id): void
    {
        $snapshot = DB::transaction(function () use ($id): array {
            /** @var Product $product */
            $product  = Product::lockForUpdate()->findOrFail($id);
            $snapshot = $product->toEventPayload();

            $product->delete(); // soft-delete

            Log::info('[ProductService] Product soft-deleted', ['id' => $id]);

            return $snapshot;
        });

        event(new ProductDeleted($snapshot));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function merge(Product $product, array $inventoryMap): array
    {
        return array_merge($product->toArray(), [
            'inventory' => $inventoryMap[$product->sku] ?? [],
        ]);
    }
}
