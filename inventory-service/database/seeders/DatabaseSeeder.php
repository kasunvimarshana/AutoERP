<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = env('SEED_TENANT_ID');

        if (!$tenantId) {
            $this->command->error('SEED_TENANT_ID environment variable is required.');
            return;
        }

        $products = [
            ['name' => 'Laptop Pro 15"',      'sku' => 'LPRO-15',  'price' => 1299.99, 'quantity' => 50],
            ['name' => 'Wireless Keyboard',   'sku' => 'WKB-001',  'price' => 79.99,   'quantity' => 200],
            ['name' => 'USB-C Hub 7-in-1',    'sku' => 'USBC-HUB', 'price' => 49.99,   'quantity' => 150],
            ['name' => 'Monitor 27" 4K',      'sku' => 'MON-27-4K','price' => 449.99,  'quantity' => 30],
            ['name' => 'Mechanical Keyboard', 'sku' => 'MKBD-001', 'price' => 129.99,  'quantity' => 100],
        ];

        foreach ($products as $p) {
            $product = Product::firstOrCreate(
                ['sku' => $p['sku']],
                [
                    'tenant_id'   => $tenantId,
                    'name'        => $p['name'],
                    'price'       => $p['price'],
                    'currency'    => 'USD',
                    'status'      => 'active',
                ]
            );

            InventoryItem::firstOrCreate(
                ['product_id' => $product->id, 'tenant_id' => $tenantId],
                [
                    'quantity_available' => $p['quantity'],
                    'quantity_reserved'  => 0,
                    'reorder_threshold'  => 10,
                    'warehouse_location' => 'Warehouse-A',
                ]
            );
        }

        $this->command->info('Inventory service seeded successfully.');
    }
}
