<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProductServiceInterface;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Business logic for product catalogue management.
 */
final class ProductService implements ProductServiceInterface
{
    /** {@inheritDoc} */
    public function list(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return Product::with('inventoryItem')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    /** {@inheritDoc} */
    public function create(string $tenantId, array $data): Product
    {
        $product = Product::create(array_merge($data, ['tenant_id' => $tenantId]));

        Log::info('Product created', ['product_id' => $product->id, 'tenant_id' => $tenantId]);

        return $product;
    }

    /** {@inheritDoc} */
    public function find(string $id, string $tenantId): ?Product
    {
        return Product::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with('inventoryItem')
            ->first();
    }

    /** {@inheritDoc} */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh(['inventoryItem']);
    }

    /** {@inheritDoc} */
    public function delete(Product $product): void
    {
        $product->delete();
        Log::info('Product deleted', ['product_id' => $product->id]);
    }
}
