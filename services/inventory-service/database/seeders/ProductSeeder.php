<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Domain\Models\Product;
use App\Domain\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 'tenant_demo';
        $category = Category::where('tenant_id', $tenantId)->first();
        $products = [
            ['sku' => 'PROD-001', 'name' => 'Laptop Pro 15"', 'unit_price' => 1299.99, 'unit_of_measure' => 'unit', 'reorder_point' => 5],
            ['sku' => 'PROD-002', 'name' => 'Wireless Mouse', 'unit_price' => 29.99, 'unit_of_measure' => 'unit', 'reorder_point' => 20],
            ['sku' => 'PROD-003', 'name' => 'USB-C Cable', 'unit_price' => 9.99, 'unit_of_measure' => 'unit', 'reorder_point' => 50],
        ];
        foreach ($products as $data) {
            Product::firstOrCreate(['tenant_id' => $tenantId, 'sku' => $data['sku']],
                array_merge($data, ['tenant_id' => $tenantId, 'category_id' => $category?->id, 'is_active' => true]));
        }
    }
}
