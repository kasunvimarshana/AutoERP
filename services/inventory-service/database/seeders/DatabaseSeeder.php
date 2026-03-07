<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('inventory_items')->insert([
            [
                'product_id' => 1,
                'product_sku' => 'PROD-001',
                'product_name' => 'Sample Product A',
                'quantity' => 100,
                'reserved_quantity' => 0,
                'minimum_quantity' => 10,
                'location' => 'Warehouse A - Shelf 1',
                'status' => 'available',
                'last_restock_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
