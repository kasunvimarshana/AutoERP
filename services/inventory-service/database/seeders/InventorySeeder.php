<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $inventories = [
            [
                'product_id'         => 1,
                'product_name'       => 'Gaming Laptop Pro',
                'product_code'       => 'PROD-001',
                'product_category'   => 'Electronics',
                'quantity'           => 50,
                'reserved_quantity'  => 5,
                'warehouse_location' => 'Warehouse A',
                'reorder_level'      => 10,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'product_id'         => 2,
                'product_name'       => 'Wireless Mouse',
                'product_code'       => 'PROD-002',
                'product_category'   => 'Electronics',
                'quantity'           => 200,
                'reserved_quantity'  => 0,
                'warehouse_location' => 'Warehouse A',
                'reorder_level'      => 20,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'product_id'         => 3,
                'product_name'       => 'Office Chair Deluxe',
                'product_code'       => 'PROD-003',
                'product_category'   => 'Furniture',
                'quantity'           => 30,
                'reserved_quantity'  => 0,
                'warehouse_location' => 'Warehouse B',
                'reorder_level'      => 5,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'product_id'         => 4,
                'product_name'       => 'Standing Desk',
                'product_code'       => 'PROD-004',
                'product_category'   => 'Furniture',
                'quantity'           => 15,
                'reserved_quantity'  => 0,
                'warehouse_location' => 'Warehouse B',
                'reorder_level'      => 5,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'product_id'         => 5,
                'product_name'       => 'Coffee Maker Pro',
                'product_code'       => 'PROD-005',
                'product_category'   => 'Appliances',
                'quantity'           => 75,
                'reserved_quantity'  => 0,
                'warehouse_location' => 'Warehouse C',
                'reorder_level'      => 10,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ];

        DB::table('inventories')->insert($inventories);
    }
}
