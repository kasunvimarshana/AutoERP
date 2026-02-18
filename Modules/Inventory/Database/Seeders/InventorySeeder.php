<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Entities\ProductCategory;
use Modules\Inventory\Entities\UnitOfMeasure;
use Modules\Inventory\Entities\Warehouse;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedUnitOfMeasures();
        $this->seedProductCategories();
        $this->seedWarehouses();
    }

    /**
     * Seed unit of measures.
     */
    protected function seedUnitOfMeasures(): void
    {
        $uoms = [
            // Length
            ['code' => 'M', 'name' => 'Meter', 'category' => 'length', 'ratio' => 1.0, 'is_base' => true],
            ['code' => 'CM', 'name' => 'Centimeter', 'category' => 'length', 'ratio' => 0.01, 'is_base' => false],
            ['code' => 'KM', 'name' => 'Kilometer', 'category' => 'length', 'ratio' => 1000.0, 'is_base' => false],

            // Weight
            ['code' => 'KG', 'name' => 'Kilogram', 'category' => 'weight', 'ratio' => 1.0, 'is_base' => true],
            ['code' => 'G', 'name' => 'Gram', 'category' => 'weight', 'ratio' => 0.001, 'is_base' => false],
            ['code' => 'T', 'name' => 'Tonne', 'category' => 'weight', 'ratio' => 1000.0, 'is_base' => false],

            // Volume
            ['code' => 'L', 'name' => 'Liter', 'category' => 'volume', 'ratio' => 1.0, 'is_base' => true],
            ['code' => 'ML', 'name' => 'Milliliter', 'category' => 'volume', 'ratio' => 0.001, 'is_base' => false],

            // Unit
            ['code' => 'UNIT', 'name' => 'Unit', 'category' => 'unit', 'ratio' => 1.0, 'is_base' => true],
            ['code' => 'DOZEN', 'name' => 'Dozen', 'category' => 'unit', 'ratio' => 12.0, 'is_base' => false],
        ];

        foreach ($uoms as $uom) {
            UnitOfMeasure::create([
                'tenant_id' => 1,
                'code' => $uom['code'],
                'name' => $uom['name'],
                'uom_category' => $uom['category'],
                'ratio' => $uom['ratio'],
                'is_base_unit' => $uom['is_base'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * Seed product categories.
     */
    protected function seedProductCategories(): void
    {
        $categories = [
            ['code' => 'RAW', 'name' => 'Raw Materials'],
            ['code' => 'FIN', 'name' => 'Finished Goods'],
            ['code' => 'WIP', 'name' => 'Work in Progress'],
            ['code' => 'SVC', 'name' => 'Services'],
        ];

        foreach ($categories as $category) {
            ProductCategory::create([
                'tenant_id' => 1,
                'code' => $category['code'],
                'name' => $category['name'],
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }
    }

    /**
     * Seed warehouses.
     */
    protected function seedWarehouses(): void
    {
        Warehouse::create([
            'tenant_id' => 1,
            'code' => 'WH-001',
            'name' => 'Main Warehouse',
            'warehouse_type' => 'main',
            'address_line1' => '123 Warehouse Street',
            'city' => 'City',
            'postal_code' => '12345',
            'country' => 'US',
            'is_active' => true,
        ]);
    }
}
