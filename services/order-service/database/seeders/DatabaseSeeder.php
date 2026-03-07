<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'order_number' => 'ORD-SAMPLE01-' . date('Ymd'),
            'user_id' => 'user-uuid-001',
            'status' => 'pending',
            'total_amount' => 79.98,
            'notes' => 'Sample order for testing',
            'metadata' => json_encode(['source' => 'web']),
            'shipping_address' => json_encode([
                'street' => '123 Main St',
                'city' => 'Springfield',
                'state' => 'IL',
                'postal_code' => '62701',
                'country' => 'US',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_items')->insert([
            [
                'order_id' => $orderId,
                'product_id' => 1,
                'product_sku' => 'PROD-001',
                'product_name' => 'Sample Product A',
                'quantity' => 2,
                'unit_price' => 29.99,
                'total_price' => 59.98,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => $orderId,
                'product_id' => 2,
                'product_sku' => 'PROD-002',
                'product_name' => 'Sample Product B',
                'quantity' => 1,
                'unit_price' => 20.00,
                'total_price' => 20.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
