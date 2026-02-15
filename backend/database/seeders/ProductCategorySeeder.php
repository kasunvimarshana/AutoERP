<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Product\Models\ProductCategory;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-company')->first();
        
        if (!$tenant) {
            $this->command->error('Tenant not found. Please run TenantSeeder first.');
            return;
        }

        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices and accessories',
                'parent_id' => null,
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Computers',
                        'slug' => 'computers',
                        'description' => 'Desktop and laptop computers',
                    ],
                    [
                        'name' => 'Mobile Phones',
                        'slug' => 'mobile-phones',
                        'description' => 'Smartphones and accessories',
                    ],
                    [
                        'name' => 'Tablets',
                        'slug' => 'tablets',
                        'description' => 'Tablet devices',
                    ],
                ],
            ],
            [
                'name' => 'Office Supplies',
                'slug' => 'office-supplies',
                'description' => 'Office equipment and supplies',
                'parent_id' => null,
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Stationery',
                        'slug' => 'stationery',
                        'description' => 'Pens, pencils, paper, etc.',
                    ],
                    [
                        'name' => 'Furniture',
                        'slug' => 'furniture',
                        'description' => 'Office furniture',
                    ],
                ],
            ],
            [
                'name' => 'Services',
                'slug' => 'services',
                'description' => 'Service products',
                'parent_id' => null,
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Consulting',
                        'slug' => 'consulting',
                        'description' => 'Consulting services',
                    ],
                    [
                        'name' => 'Maintenance',
                        'slug' => 'maintenance',
                        'description' => 'Maintenance and repair services',
                    ],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);
            
            $category = ProductCategory::create($categoryData);
            
            foreach ($children as $childData) {
                ProductCategory::create([
                    'name' => $childData['name'],
                    'slug' => $childData['slug'],
                    'description' => $childData['description'],
                    'parent_id' => $category->id,
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Product categories seeded successfully!');
    }
}
