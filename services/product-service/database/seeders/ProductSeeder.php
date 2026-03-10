<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name'           => 'Gaming Laptop Pro',
                'code'           => 'PROD-001',
                'category'       => 'Electronics',
                'description'    => 'High-performance gaming laptop with RTX 4070 and 16GB RAM.',
                'price'          => 1299.99,
                'stock_quantity' => 50,
                'image_url'      => null,
                'is_active'      => true,
            ],
            [
                'name'           => 'Wireless Mouse',
                'code'           => 'PROD-002',
                'category'       => 'Electronics',
                'description'    => 'Ergonomic wireless mouse with long battery life.',
                'price'          => 29.99,
                'stock_quantity' => 200,
                'image_url'      => null,
                'is_active'      => true,
            ],
            [
                'name'           => 'Office Chair Deluxe',
                'code'           => 'PROD-003',
                'category'       => 'Furniture',
                'description'    => 'Comfortable office chair with lumbar support and adjustable height.',
                'price'          => 399.99,
                'stock_quantity' => 30,
                'image_url'      => null,
                'is_active'      => true,
            ],
            [
                'name'           => 'Standing Desk',
                'code'           => 'PROD-004',
                'category'       => 'Furniture',
                'description'    => 'Electric height-adjustable standing desk, 140x70cm surface.',
                'price'          => 599.99,
                'stock_quantity' => 20,
                'image_url'      => null,
                'is_active'      => true,
            ],
            [
                'name'           => 'Coffee Maker Pro',
                'code'           => 'PROD-005',
                'category'       => 'Appliances',
                'description'    => 'Programmable coffee maker with built-in grinder and thermal carafe.',
                'price'          => 149.99,
                'stock_quantity' => 75,
                'image_url'      => null,
                'is_active'      => true,
            ],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['code' => $product['code']],
                array_merge($product, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
