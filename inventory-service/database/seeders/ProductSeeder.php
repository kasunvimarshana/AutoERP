<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['sku' => 'LAPTOP-001',  'name' => 'Pro Laptop 15"',         'description' => 'High-performance laptop with 16GB RAM, 512GB SSD', 'price' => 1299.99, 'stock_quantity' => 50],
            ['sku' => 'PHONE-001',   'name' => 'Smartphone X12',         'description' => 'Latest flagship smartphone with 5G',               'price' =>  899.99, 'stock_quantity' => 100],
            ['sku' => 'TABLET-001',  'name' => 'Tablet Pro 11"',         'description' => '11-inch tablet with stylus support',               'price' =>  649.99, 'stock_quantity' => 75],
            ['sku' => 'HEADPHONE-001','name' => 'Wireless Headphones ANC','description' => 'Active noise cancelling headphones',              'price' =>  299.99, 'stock_quantity' => 200],
            ['sku' => 'MONITOR-001', 'name' => '4K Monitor 27"',         'description' => '27-inch 4K UHD monitor, 144Hz',                   'price' =>  549.99, 'stock_quantity' => 30],
            ['sku' => 'KEYBOARD-001','name' => 'Mechanical Keyboard TKL','description' => 'Tenkeyless mechanical keyboard, RGB backlit',      'price' =>  149.99, 'stock_quantity' => 150],
            ['sku' => 'MOUSE-001',   'name' => 'Ergonomic Mouse Pro',    'description' => 'Wireless ergonomic mouse, 4000 DPI',               'price' =>   79.99, 'stock_quantity' => 300],
            ['sku' => 'WEBCAM-001',  'name' => '4K Webcam',              'description' => '4K webcam with built-in mic and light',           'price' =>  199.99, 'stock_quantity' => 80],
            ['sku' => 'SSD-001',     'name' => 'NVMe SSD 1TB',           'description' => '1TB NVMe M.2 SSD, 7000MB/s read speed',           'price' =>  119.99, 'stock_quantity' => 250],
            ['sku' => 'DOCK-001',    'name' => 'USB-C Docking Station',  'description' => '12-in-1 USB-C dock with dual HDMI',               'price' =>  249.99, 'stock_quantity' => 60],
        ];

        foreach ($products as $data) {
            Product::firstOrCreate(
                ['sku' => $data['sku']],
                array_merge($data, [
                    'id'                => Uuid::uuid4()->toString(),
                    'reserved_quantity' => 0,
                ])
            );
        }
    }
}
