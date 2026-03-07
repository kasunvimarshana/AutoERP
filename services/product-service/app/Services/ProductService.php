<?php

namespace App\Services;

use App\DTOs\ProductDTO;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Jobs\SyncInventoryJob;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Webhooks\WebhookDispatcher;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly WebhookDispatcher $webhookDispatcher,
    ) {}

    /**
     * Return a paginated, filtered, sorted list of products for the current tenant.
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($perPage);
    }

    /**
     * Return a single product or throw a 404-equivalent.
     */
    public function findOrFail(int $id): Product
    {
        $product = $this->productRepository->findById($id);
        if (! $product) {
            abort(404, "Product #{$id} not found");
        }
        return $product;
    }

    /**
     * Create a new product within a DB transaction, fire events, and kick off the Saga.
     */
    public function create(ProductDTO $dto, string $triggeredBy): Product
    {
        $this->assertSkuUnique($dto->sku, $dto->tenantId);

        $product = DB::transaction(function () use ($dto): Product {
            return $this->productRepository->create($dto);
        });

        $event = new ProductCreated($product, $dto->tenantId, $triggeredBy);
        event($event);

        // Saga Step 1: sync with inventory-service
        SyncInventoryJob::dispatch(
            productId:   $product->id,
            sku:         $product->sku,
            tenantId:    $dto->tenantId,
            triggeredBy: $triggeredBy,
        );

        // Dispatch webhook asynchronously
        $this->webhookDispatcher->dispatch('product.created', $dto->tenantId, $product->toArray());

        Log::info('Product created', ['product_id' => $product->id, 'tenant_id' => $dto->tenantId]);

        return $product;
    }

    /**
     * Update a product within a DB transaction and fire events.
     */
    public function update(Product $product, ProductDTO $dto, string $triggeredBy): Product
    {
        if ($dto->sku !== $product->sku) {
            $this->assertSkuUnique($dto->sku, $dto->tenantId, excludeId: $product->id);
        }

        $original = $product->toArray();

        $updated = DB::transaction(function () use ($product, $dto): Product {
            return $this->productRepository->update($product, $dto);
        });

        $changes = array_diff_assoc($updated->toArray(), $original);
        unset($changes['updated_at']); // skip timestamp noise

        if (! empty($changes)) {
            event(new ProductUpdated($updated, $changes, $dto->tenantId, $triggeredBy));
            $this->webhookDispatcher->dispatch('product.updated', $dto->tenantId, $updated->toArray());
        }

        return $updated;
    }

    /**
     * Soft-delete a product within a DB transaction and fire events.
     */
    public function delete(Product $product, string $triggeredBy): void
    {
        $productId = $product->id;
        $sku       = $product->sku;
        $tenantId  = $product->tenant_id;

        DB::transaction(function () use ($product): void {
            $this->productRepository->delete($product);
        });

        event(new ProductDeleted($productId, $sku, $tenantId, $triggeredBy));
        $this->webhookDispatcher->dispatch('product.deleted', $tenantId, [
            'product_id' => $productId,
            'sku'        => $sku,
        ]);

        Log::info('Product deleted', ['product_id' => $productId, 'tenant_id' => $tenantId]);
    }

    private function assertSkuUnique(string $sku, string $tenantId, ?int $excludeId = null): void
    {
        $existing = $this->productRepository->findBySku($sku, $tenantId);
        if ($existing && $existing->id !== $excludeId) {
            abort(422, "SKU '{$sku}' already exists for this tenant");
        }
    }
}
