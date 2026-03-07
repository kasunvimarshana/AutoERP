<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with sample products.
     */
    public function run(): void
    {
        // Create sample products across categories
        $products = [
            [
                'name'        => 'Wireless Keyboard',
                'description' => 'Bluetooth wireless keyboard with long battery life',
                'price'       => 49.99,
                'stock'       => 150,
                'sku'         => 'WK-001',
                'category'    => 'Electronics',
                'is_active'   => true,
            ],
            [
                'name'        => 'USB-C Hub',
                'description' => '7-in-1 USB-C hub with HDMI, USB 3.0, and SD card reader',
                'price'       => 35.99,
                'stock'       => 200,
                'sku'         => 'UCH-001',
                'category'    => 'Electronics',
                'is_active'   => true,
            ],
            [
                'name'        => 'Clean Code Book',
                'description' => 'A handbook of agile software craftsmanship by Robert C. Martin',
                'price'       => 29.99,
                'stock'       => 75,
                'sku'         => 'BK-001',
                'category'    => 'Books',
                'is_active'   => true,
            ],
            [
                'name'        => 'Developer T-Shirt',
                'description' => '"Hello World" printed cotton t-shirt',
                'price'       => 19.99,
                'stock'       => 300,
                'sku'         => 'CLT-001',
                'category'    => 'Clothing',
                'is_active'   => true,
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }

        // Also generate 20 random products with factory
        Product::factory()->count(20)->create();
    }
}
