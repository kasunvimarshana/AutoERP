<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    private const DEMO_TENANT = 'tenant-demo-001';

    public function run(): void
    {
        $roots = [
            ['name' => 'Electronics',  'slug' => 'electronics',  'sort_order' => 1],
            ['name' => 'Clothing',     'slug' => 'clothing',     'sort_order' => 2],
            ['name' => 'Home & Garden','slug' => 'home-garden',  'sort_order' => 3],
            ['name' => 'Sports',       'slug' => 'sports',       'sort_order' => 4],
        ];

        foreach ($roots as $root) {
            Category::firstOrCreate(
                ['tenant_id' => self::DEMO_TENANT, 'slug' => $root['slug']],
                array_merge($root, ['tenant_id' => self::DEMO_TENANT, 'is_active' => true])
            );
        }

        $electronics = Category::where('tenant_id', self::DEMO_TENANT)
            ->where('slug', 'electronics')
            ->first();

        if ($electronics) {
            $subs = [
                ['name' => 'Smartphones', 'slug' => 'smartphones'],
                ['name' => 'Laptops',     'slug' => 'laptops'],
                ['name' => 'Accessories', 'slug' => 'accessories'],
            ];
            foreach ($subs as $sub) {
                Category::firstOrCreate(
                    ['tenant_id' => self::DEMO_TENANT, 'slug' => $sub['slug']],
                    array_merge($sub, [
                        'tenant_id' => self::DEMO_TENANT,
                        'parent_id' => $electronics->id,
                        'is_active' => true,
                    ])
                );
            }
        }
    }
}
