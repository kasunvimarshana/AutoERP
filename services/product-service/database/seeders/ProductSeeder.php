<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private const DEMO_TENANT = 'tenant-demo-001';

    public function run(): void
    {
        $smartphonesCategory = Category::withoutGlobalScope('tenant')
            ->where('tenant_id', self::DEMO_TENANT)
            ->where('slug', 'smartphones')
            ->first();

        $laptopsCategory = Category::withoutGlobalScope('tenant')
            ->where('tenant_id', self::DEMO_TENANT)
            ->where('slug', 'laptops')
            ->first();

        $products = [
            [
                'tenant_id'   => self::DEMO_TENANT,
                'category_id' => $smartphonesCategory?->id,
                'sku'         => 'PHONE-001',
                'name'        => 'Demo Smartphone X1',
                'description' => 'A high-performance demo smartphone.',
                'price'       => 799.99,
                'cost_price'  => 450.00,
                'currency'    => 'USD',
                'unit'        => 'piece',
                'status'      => 'active',
                'is_active'   => true,
                'tags'        => ['smartphone', 'flagship'],
            ],
            [
                'tenant_id'   => self::DEMO_TENANT,
                'category_id' => $laptopsCategory?->id,
                'sku'         => 'LAPTOP-001',
                'name'        => 'Demo Laptop Pro 15',
                'description' => 'A professional-grade demo laptop.',
                'price'       => 1299.99,
                'cost_price'  => 800.00,
                'currency'    => 'USD',
                'unit'        => 'piece',
                'status'      => 'active',
                'is_active'   => true,
                'tags'        => ['laptop', 'pro'],
            ],
            [
                'tenant_id'   => self::DEMO_TENANT,
                'category_id' => null,
                'sku'         => 'ACC-001',
                'name'        => 'Demo USB-C Cable',
                'description' => 'A durable 2m USB-C cable.',
                'price'       => 19.99,
                'cost_price'  => 5.00,
                'currency'    => 'USD',
                'unit'        => 'piece',
                'status'      => 'active',
                'is_active'   => true,
                'tags'        => ['accessory', 'cable'],
            ],
        ];

        foreach ($products as $productData) {
            Product::withoutGlobalScope('tenant')->firstOrCreate(
                ['tenant_id' => self::DEMO_TENANT, 'sku' => $productData['sku']],
                $productData
            );
        }
    }
}
