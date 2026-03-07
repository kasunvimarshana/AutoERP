<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'name' => 'Sample Product A',
                'description' => 'A sample product for testing',
                'sku' => 'PROD-001',
                'price' => 29.99,
                'category' => 'Electronics',
                'status' => 'active',
                'metadata' => json_encode(['color' => 'black', 'weight' => '0.5kg']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sample Product B',
                'description' => 'Another sample product',
                'sku' => 'PROD-002',
                'price' => 49.99,
                'category' => 'Clothing',
                'status' => 'active',
                'metadata' => json_encode(['size' => 'M', 'color' => 'blue']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
