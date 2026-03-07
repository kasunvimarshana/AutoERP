<?php

namespace Database\Seeders;

use App\Modules\Product\Models\Product;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private array $products = [
        ['name' => 'Wireless Keyboard',      'sku' => 'ELE-001', 'price' => 49.99,  'cost' => 22.00, 'category' => 'electronics', 'unit' => 'pcs'],
        ['name' => 'USB-C Hub',              'sku' => 'ELE-002', 'price' => 35.00,  'cost' => 14.00, 'category' => 'electronics', 'unit' => 'pcs'],
        ['name' => 'Ergonomic Mouse',        'sku' => 'ELE-003', 'price' => 59.99,  'cost' => 27.00, 'category' => 'electronics', 'unit' => 'pcs'],
        ['name' => 'Monitor Stand',          'sku' => 'OFF-001', 'price' => 29.99,  'cost' => 12.00, 'category' => 'office',      'unit' => 'pcs'],
        ['name' => 'Desk Organizer',         'sku' => 'OFF-002', 'price' => 19.99,  'cost' => 8.00,  'category' => 'office',      'unit' => 'pcs'],
        ['name' => 'Ballpoint Pen (Box)',    'sku' => 'STA-001', 'price' => 5.99,   'cost' => 2.00,  'category' => 'stationery',  'unit' => 'box'],
        ['name' => 'Notebook A4',            'sku' => 'STA-002', 'price' => 4.50,   'cost' => 1.50,  'category' => 'stationery',  'unit' => 'pcs'],
        ['name' => 'Sticky Notes Pack',      'sku' => 'STA-003', 'price' => 3.99,   'cost' => 1.20,  'category' => 'stationery',  'unit' => 'pack'],
        ['name' => 'HDMI Cable 2m',          'sku' => 'CBL-001', 'price' => 12.99,  'cost' => 4.00,  'category' => 'cables',      'unit' => 'pcs'],
        ['name' => 'Power Strip 6-outlet',   'sku' => 'ELE-004', 'price' => 22.50,  'cost' => 9.00,  'category' => 'electronics', 'unit' => 'pcs'],
    ];

    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            foreach ($this->products as $product) {
                Product::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'sku' => $product['sku']],
                    [
                        'tenant_id'   => $tenant->id,
                        'name'        => $product['name'],
                        'sku'         => $product['sku'],
                        'description' => "High-quality {$product['name']} for professional use.",
                        'price'       => $product['price'],
                        'cost_price'  => $product['cost'],
                        'category'    => $product['category'],
                        'unit'        => $product['unit'],
                        'is_active'   => true,
                    ]
                );
            }
        }
    }
}
