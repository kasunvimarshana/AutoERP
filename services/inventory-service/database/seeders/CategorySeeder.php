<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Domain\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 'tenant_demo';
        $roots = ['Electronics', 'Clothing', 'Food & Beverage', 'Office Supplies'];
        foreach ($roots as $i => $name) {
            Category::firstOrCreate(['tenant_id' => $tenantId, 'slug' => Str::slug($name)], [
                'name' => $name, 'sort_order' => $i, 'is_active' => true,
            ]);
        }
    }
}
