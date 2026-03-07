<?php
namespace Database\Seeders;
use App\Models\Inventory;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()->instance('current_tenant_id', 1);
        $warehouse = Warehouse::create([
            'tenant_id' => 1, 'name' => 'Main Warehouse', 'code' => 'MAIN',
            'city' => 'New York', 'country' => 'US', 'is_active' => true,
        ]);
        Inventory::create([
            'tenant_id' => 1, 'product_id' => 1,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100, 'reserved_quantity' => 0,
            'reorder_level' => 10,
        ]);
    }
}
