<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SyncExternalData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly int $tenantId,
        private readonly string $syncType,
        private readonly array $configuration
    ) {}

    public function handle(): void
    {
        Log::info("Starting external data sync", [
            'tenant_id' => $this->tenantId,
            'sync_type' => $this->syncType,
        ]);

        match ($this->syncType) {
            'products' => $this->syncProducts(),
            'customers' => $this->syncCustomers(),
            'inventory' => $this->syncInventory(),
            'orders' => $this->syncOrders(),
            default => throw new \InvalidArgumentException("Unknown sync type: {$this->syncType}"),
        };

        // Update sync log
        DB::table('sync_logs')->insert([
            'tenant_id' => $this->tenantId,
            'sync_type' => $this->syncType,
            'status' => 'completed',
            'synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info("External data sync completed");
    }

    private function syncProducts(): void
    {
        $apiUrl = $this->configuration['api_url'] ?? null;
        $apiKey = $this->configuration['api_key'] ?? null;

        if (!$apiUrl || !$apiKey) {
            throw new \RuntimeException("Missing API configuration for products sync");
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Accept' => 'application/json',
        ])->get("{$apiUrl}/products");

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch products from external API");
        }

        $products = $response->json('data', []);

        DB::transaction(function () use ($products) {
            foreach ($products as $productData) {
                DB::table('products')->updateOrInsert(
                    [
                        'tenant_id' => $this->tenantId,
                        'external_id' => $productData['id'],
                    ],
                    [
                        'sku' => $productData['sku'],
                        'name' => $productData['name'],
                        'description' => $productData['description'] ?? null,
                        'selling_price' => $productData['price'] ?? 0,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        Log::info("Synced products", ['count' => count($products)]);
    }

    private function syncCustomers(): void
    {
        $apiUrl = $this->configuration['api_url'] ?? null;
        $apiKey = $this->configuration['api_key'] ?? null;

        if (!$apiUrl || !$apiKey) {
            throw new \RuntimeException("Missing API configuration for customers sync");
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Accept' => 'application/json',
        ])->get("{$apiUrl}/customers");

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch customers from external API");
        }

        $customers = $response->json('data', []);

        DB::transaction(function () use ($customers) {
            foreach ($customers as $customerData) {
                DB::table('customers')->updateOrInsert(
                    [
                        'tenant_id' => $this->tenantId,
                        'external_id' => $customerData['id'],
                    ],
                    [
                        'name' => $customerData['name'],
                        'email' => $customerData['email'] ?? null,
                        'phone' => $customerData['phone'] ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        Log::info("Synced customers", ['count' => count($customers)]);
    }

    private function syncInventory(): void
    {
        Log::info("Inventory sync placeholder - implement based on external system");
    }

    private function syncOrders(): void
    {
        Log::info("Orders sync placeholder - implement based on external system");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("External data sync failed", [
            'tenant_id' => $this->tenantId,
            'sync_type' => $this->syncType,
            'error' => $exception->getMessage(),
        ]);

        DB::table('sync_logs')->insert([
            'tenant_id' => $this->tenantId,
            'sync_type' => $this->syncType,
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'failed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
