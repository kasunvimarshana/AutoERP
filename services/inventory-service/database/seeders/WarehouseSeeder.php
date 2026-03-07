<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Seed default warehouses for the demo tenant (tenant_id = 1).
     */
    public function run(): void
    {
        $warehouses = [
            [
                'tenant_id' => 1,
                'name'      => 'Main Warehouse',
                'code'      => 'MAIN-001',
                'address'   => '123 Logistics Ave',
                'city'      => 'New York',
                'country'   => 'US',
                'is_active' => true,
            ],
            [
                'tenant_id' => 1,
                'name'      => 'West Coast Warehouse',
                'code'      => 'WEST-001',
                'address'   => '456 Harbor Blvd',
                'city'      => 'Los Angeles',
                'country'   => 'US',
                'is_active' => true,
            ],
            [
                'tenant_id' => 1,
                'name'      => 'Returns Warehouse',
                'code'      => 'RET-001',
                'address'   => '789 Returns St',
                'city'      => 'Chicago',
                'country'   => 'US',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $data) {
            Warehouse::firstOrCreate(
                ['tenant_id' => $data['tenant_id'], 'code' => $data['code']],
                $data
            );
        }

        $this->command->info('Default warehouses seeded successfully.');
    }
}
