<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Domain\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 'tenant_demo';
        $warehouses = [
            ['code' => 'WH-MAIN', 'name' => 'Main Warehouse', 'type' => 'standard'],
            ['code' => 'WH-DIST', 'name' => 'Distribution Center', 'type' => 'distribution'],
        ];
        foreach ($warehouses as $data) {
            Warehouse::firstOrCreate(['tenant_id' => $tenantId, 'code' => $data['code']],
                array_merge($data, ['tenant_id' => $tenantId, 'is_active' => true]));
        }
    }
}
