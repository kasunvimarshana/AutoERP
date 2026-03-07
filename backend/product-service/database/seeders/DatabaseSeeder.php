<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()->instance('current_tenant_id', 1);

        $category = Category::create([
            'tenant_id' => 1,
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic products',
            'is_active' => true,
        ]);

        Product::create([
            'tenant_id' => 1,
            'category_id' => $category->id,
            'name' => 'Sample Laptop',
            'sku' => 'LAPTOP-001',
            'description' => 'A sample laptop product',
            'price' => 999.99,
            'cost' => 700.00,
            'is_active' => true,
        ]);
    }
}
