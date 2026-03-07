<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrossServiceInventoryService
{
    private string $productServiceUrl;

    public function __construct()
    {
        $this->productServiceUrl = rtrim(
            config('services.product_service.url', 'http://product-service'),
            '/'
        );
    }

    // -------------------------------------------------------------------------
    // Product Service calls
    // -------------------------------------------------------------------------

    /**
     * Fetch product details for one or more product IDs from the Product Service.
     *
     * Calls: GET /api/products/by-ids?ids[]=uuid1&ids[]=uuid2
     *
     * @param  string[]  $productIds
     * @return array<string, array>  Keyed by product_id
     */
    public function getProductDetails(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $url = $this->productServiceUrl . '/api/products/by-ids';

        try {
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'X-Service-Key' => config('services.internal_key', ''),
            ])
                ->timeout(10)
                ->retry(2, 200)
                ->get($url, ['ids' => $productIds]);

            if (! $response->successful()) {
                Log::warning('CrossServiceInventoryService: product fetch failed', [
                    'status' => $response->status(),
                    'url'    => $url,
                ]);

                return [];
            }

            $body = $response->json();
            $items = $body['data'] ?? $body ?? [];

            // Re-index by product_id for O(1) lookups
            $indexed = [];
            foreach ($items as $product) {
                if (isset($product['id'])) {
                    $indexed[$product['id']] = $product;
                }
            }

            return $indexed;
        } catch (\Throwable $e) {
            Log::error('CrossServiceInventoryService: exception fetching products', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Merge product details into a collection of inventory items.
     *
     * Each inventory item is expected to have a `product_id` field.
     * The resulting items gain a `product` key containing the product data
     * (or null if the product could not be found).
     *
     * @param  iterable<array|\App\Models\Inventory>  $inventoryItems
     * @return array[]
     */
    public function enrichInventoryWithProducts(iterable $inventoryItems): array
    {
        $items = [];
        $productIds = [];

        foreach ($inventoryItems as $item) {
            $arr = is_array($item) ? $item : $item->toArray();
            $items[] = $arr;
            if (! empty($arr['product_id'])) {
                $productIds[] = $arr['product_id'];
            }
        }

        $productIds = array_unique($productIds);

        if (empty($productIds)) {
            return array_map(fn ($i) => array_merge($i, ['product' => null]), $items);
        }

        $products = $this->getProductDetails($productIds);

        return array_map(function (array $item) use ($products) {
            $productId       = $item['product_id'] ?? null;
            $item['product'] = $productId ? ($products[$productId] ?? null) : null;

            return $item;
        }, $items);
    }

    /**
     * Search products by name via the Product Service and return matching IDs.
     *
     * Calls: GET /api/products?search=<name>&per_page=200
     *
     * @return string[]  Array of product UUIDs
     */
    public function searchProductIdsByName(string $productName): array
    {
        $url = $this->productServiceUrl . '/api/products';

        try {
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'X-Service-Key' => config('services.internal_key', ''),
            ])
                ->timeout(10)
                ->retry(2, 200)
                ->get($url, ['search' => $productName, 'per_page' => 200]);

            if (! $response->successful()) {
                return [];
            }

            $body  = $response->json();
            $items = $body['data'] ?? [];

            // When the response contains pagination, data may be nested
            if (isset($items['data'])) {
                $items = $items['data'];
            }

            return array_column($items, 'id');
        } catch (\Throwable $e) {
            Log::error('CrossServiceInventoryService: exception searching products', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
