<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private const TENANT_ID = 'tenant-001';

    public function run(): void
    {
        // Create a default warehouse for the demo tenant.
        $warehouse = Warehouse::firstOrCreate(
            ['tenant_id' => self::TENANT_ID, 'code' => 'WH-MAIN'],
            [
                'name'      => 'Main Warehouse',
                'address'   => [
                    'street' => '123 Logistics Ave',
                    'city'   => 'Commerce City',
                    'state'  => 'CA',
                    'zip'    => '90210',
                    'country' => 'US',
                ],
                'is_active' => true,
            ]
        );

        $products = [
            // Electronics (4 products)
            [
                'category'   => 'Electronics',
                'sku'        => 'ELEC-001',
                'name'       => 'Wireless Bluetooth Headphones',
                'unit_price' => 79.99,
                'qty'        => 150,
            ],
            [
                'category'   => 'Electronics',
                'sku'        => 'ELEC-002',
                'name'       => '4K Ultra HD Monitor 27"',
                'unit_price' => 349.99,
                'qty'        => 45,
            ],
            [
                'category'   => 'Electronics',
                'sku'        => 'ELEC-003',
                'name'       => 'Mechanical Keyboard – TKL',
                'unit_price' => 129.99,
                'qty'        => 80,
            ],
            [
                'category'   => 'Electronics',
                'sku'        => 'ELEC-004',
                'name'       => 'USB-C Docking Station',
                'unit_price' => 89.99,
                'qty'        => 60,
            ],
            // Clothing (4 products)
            [
                'category'   => 'Clothing',
                'sku'        => 'CLTH-001',
                'name'       => 'Merino Wool Crew Sweater – Navy',
                'unit_price' => 64.99,
                'qty'        => 200,
            ],
            [
                'category'   => 'Clothing',
                'sku'        => 'CLTH-002',
                'name'       => 'Slim-Fit Chino Trousers',
                'unit_price' => 49.99,
                'qty'        => 175,
            ],
            [
                'category'   => 'Clothing',
                'sku'        => 'CLTH-003',
                'name'       => 'Performance Running Jacket',
                'unit_price' => 89.99,
                'qty'        => 95,
            ],
            [
                'category'   => 'Clothing',
                'sku'        => 'CLTH-004',
                'name'       => 'Classic Oxford Shirt – White',
                'unit_price' => 44.99,
                'qty'        => 300,
            ],
            // Food (4 products)
            [
                'category'   => 'Food',
                'sku'        => 'FOOD-001',
                'name'       => 'Organic Extra Virgin Olive Oil 500ml',
                'unit_price' => 12.99,
                'qty'        => 500,
            ],
            [
                'category'   => 'Food',
                'sku'        => 'FOOD-002',
                'name'       => 'Dark Chocolate 70% – 200g Bar',
                'unit_price' => 4.99,
                'qty'        => 1000,
            ],
            [
                'category'   => 'Food',
                'sku'        => 'FOOD-003',
                'name'       => 'Colombian Single-Origin Coffee – 250g',
                'unit_price' => 14.99,
                'qty'        => 400,
            ],
            [
                'category'   => 'Food',
                'sku'        => 'FOOD-004',
                'name'       => 'Himalayan Pink Salt – 500g',
                'unit_price' => 6.99,
                'qty'        => 750,
            ],
            // Books (4 products)
            [
                'category'   => 'Books',
                'sku'        => 'BOOK-001',
                'name'       => 'Domain-Driven Design – Eric Evans',
                'unit_price' => 49.99,
                'qty'        => 120,
            ],
            [
                'category'   => 'Books',
                'sku'        => 'BOOK-002',
                'name'       => 'Clean Code – Robert C. Martin',
                'unit_price' => 39.99,
                'qty'        => 200,
            ],
            [
                'category'   => 'Books',
                'sku'        => 'BOOK-003',
                'name'       => 'Designing Data-Intensive Applications',
                'unit_price' => 54.99,
                'qty'        => 90,
            ],
            [
                'category'   => 'Books',
                'sku'        => 'BOOK-004',
                'name'       => 'The Pragmatic Programmer – 20th Anniversary',
                'unit_price' => 44.99,
                'qty'        => 110,
            ],
            // Tools (4 products)
            [
                'category'   => 'Tools',
                'sku'        => 'TOOL-001',
                'name'       => 'Cordless Drill – 18V',
                'unit_price' => 129.99,
                'qty'        => 70,
            ],
            [
                'category'   => 'Tools',
                'sku'        => 'TOOL-002',
                'name'       => 'Digital Vernier Caliper – 150mm',
                'unit_price' => 24.99,
                'qty'        => 180,
            ],
            [
                'category'   => 'Tools',
                'sku'        => 'TOOL-003',
                'name'       => 'Precision Screwdriver Set (32-piece)',
                'unit_price' => 34.99,
                'qty'        => 250,
            ],
            [
                'category'   => 'Tools',
                'sku'        => 'TOOL-004',
                'name'       => 'Adjustable Torque Wrench 1/2"',
                'unit_price' => 59.99,
                'qty'        => 55,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::firstOrCreate(
                ['tenant_id' => self::TENANT_ID, 'sku' => $productData['sku']],
                [
                    'name'       => $productData['name'],
                    'category'   => $productData['category'],
                    'unit_price' => $productData['unit_price'],
                    'currency'   => 'USD',
                    'is_active'  => true,
                    'metadata'   => ['seeded' => true],
                ]
            );

            InventoryItem::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                [
                    'tenant_id'          => self::TENANT_ID,
                    'quantity_available' => $productData['qty'],
                    'quantity_reserved'  => 0,
                    'quantity_sold'      => 0,
                    'reorder_level'      => (int) ($productData['qty'] * 0.1),
                    'max_stock_level'    => $productData['qty'] * 5,
                    'unit_of_measure'    => 'unit',
                ]
            );
        }

        $this->command->info('ProductSeeder: 20 products with inventory records created.');
    }
}
