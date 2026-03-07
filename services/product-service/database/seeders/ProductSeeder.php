<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name'           => 'Wireless Bluetooth Headphones',
                'description'    => 'Premium noise-cancelling wireless headphones with 30-hour battery life.',
                'sku'            => 'ELEC-WBH-001',
                'price'          => 129.9900,
                'category'       => 'Electronics',
                'status'         => 'active',
                'stock_quantity' => 150,
            ],
            [
                'name'           => 'Ergonomic Office Chair',
                'description'    => 'Adjustable lumbar support chair with breathable mesh back.',
                'sku'            => 'FURN-EOC-002',
                'price'          => 349.9900,
                'category'       => 'Furniture',
                'status'         => 'active',
                'stock_quantity' => 45,
            ],
            [
                'name'           => 'Stainless Steel Water Bottle',
                'description'    => '1-litre insulated bottle, keeps drinks cold for 24 hours.',
                'sku'            => 'KITCH-SWB-003',
                'price'          => 24.9900,
                'category'       => 'Kitchen',
                'status'         => 'active',
                'stock_quantity' => 300,
            ],
            [
                'name'           => 'Mechanical Keyboard',
                'description'    => 'Tenkeyless mechanical keyboard with Cherry MX Blue switches.',
                'sku'            => 'ELEC-MK-004',
                'price'          => 89.9900,
                'category'       => 'Electronics',
                'status'         => 'active',
                'stock_quantity' => 80,
            ],
            [
                'name'           => 'Running Shoes Pro',
                'description'    => 'Lightweight trail running shoes with responsive cushioning.',
                'sku'            => 'SPORT-RSP-005',
                'price'          => 119.9900,
                'category'       => 'Sports',
                'status'         => 'active',
                'stock_quantity' => 0,
            ],
            [
                'name'           => 'Yoga Mat',
                'description'    => 'Non-slip 6mm thick eco-friendly yoga mat.',
                'sku'            => 'SPORT-YM-006',
                'price'          => 39.9900,
                'category'       => 'Sports',
                'status'         => 'inactive',
                'stock_quantity' => 200,
            ],
            [
                'name'           => 'Smart LED Desk Lamp',
                'description'    => 'USB-C rechargeable desk lamp with adjustable colour temperature.',
                'sku'            => 'ELEC-SLDL-007',
                'price'          => 54.9900,
                'category'       => 'Electronics',
                'status'         => 'active',
                'stock_quantity' => 120,
            ],
            [
                'name'           => 'Coffee Maker Deluxe',
                'description'    => '12-cup programmable drip coffee maker with built-in grinder.',
                'sku'            => 'KITCH-CMD-008',
                'price'          => 79.9900,
                'category'       => 'Kitchen',
                'status'         => 'active',
                'stock_quantity' => 60,
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['sku' => $productData['sku']],
                $productData
            );
        }

        $this->command->info('Products seeded successfully.');
    }
}
