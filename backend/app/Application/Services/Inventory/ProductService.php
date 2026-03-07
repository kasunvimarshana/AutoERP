<?php

declare(strict_types=1);

namespace App\Application\Services\Inventory;

use App\Application\DTOs\ProductDTO;
use App\Domain\Inventory\Contracts\InventoryRepositoryInterface;
use App\Domain\Inventory\Events\ProductCreated;
use App\Domain\Inventory\Events\ProductUpdated;
use App\Domain\Inventory\Events\StockAdjusted;
use App\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Application service for Product / Inventory management.
 *
 * Orchestrates repository calls, domain events, and message broker publishing.
 * Controllers must remain thin and delegate all business logic here.
 */
final class ProductService
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly MessageBrokerInterface $messageBroker,
    ) {}

    /**
     * List products with filtering / sorting / pagination.
     */
    public function list(array $filters = []): mixed
    {
        return $this->inventoryRepository->all($filters);
    }

    /**
     * Get a single product by id.
     */
    public function get(int|string $id): Model
    {
        return $this->inventoryRepository->findOrFail($id);
    }

    /**
     * Create a new product and publish domain events.
     */
    public function create(ProductDTO $dto, int|string $tenantId): Model
    {
        return DB::transaction(function () use ($dto, $tenantId): Model {
            $data = array_merge($dto->toArray(), ['tenant_id' => $tenantId]);

            $product = $this->inventoryRepository->create($data);

            $userId = Auth::id();
            event(new ProductCreated($product, $tenantId, $userId));

            $this->messageBroker->publish('inventory.product.created', [
                'product_id' => $product->id,
                'tenant_id'  => $tenantId,
                'sku'        => $product->sku,
                'name'       => $product->name,
            ]);

            Log::info("[ProductService] Product #{$product->id} created by user #{$userId}.");

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function update(int|string $id, array $attributes): Model
    {
        return DB::transaction(function () use ($id, $attributes): Model {
            $before  = $this->inventoryRepository->findOrFail($id);
            $product = $this->inventoryRepository->update($id, $attributes);

            $changed = array_keys(array_diff_assoc($attributes, $before->toArray()));

            event(new ProductUpdated($product, $changed, $product->tenant_id, Auth::id()));

            $this->messageBroker->publish('inventory.product.updated', [
                'product_id' => $product->id,
                'tenant_id'  => $product->tenant_id,
                'changes'    => $changed,
            ]);

            return $product;
        });
    }

    /**
     * Delete a product (soft delete).
     */
    public function delete(int|string $id): bool
    {
        return DB::transaction(fn () => $this->inventoryRepository->delete($id));
    }

    /**
     * Adjust stock and fire the StockAdjusted event.
     */
    public function adjustStock(int|string $productId, int $delta, string $reason = 'manual'): Model
    {
        return DB::transaction(function () use ($productId, $delta, $reason): Model {
            $before  = $this->inventoryRepository->findOrFail($productId);
            $product = $this->inventoryRepository->adjustStock($productId, $delta);

            event(new StockAdjusted(
                $product,
                $delta,
                $before->stock_quantity,
                $product->stock_quantity,
                $reason,
                $product->tenant_id,
                Auth::id()
            ));

            $this->messageBroker->publish('inventory.stock.adjusted', [
                'product_id'        => $product->id,
                'tenant_id'         => $product->tenant_id,
                'delta'             => $delta,
                'previous_quantity' => $before->stock_quantity,
                'new_quantity'      => $product->stock_quantity,
                'reason'            => $reason,
            ]);

            return $product;
        });
    }

    /**
     * Return products with low stock for the current tenant.
     */
    public function getLowStock(): Collection
    {
        return $this->inventoryRepository->findLowStock();
    }
}
