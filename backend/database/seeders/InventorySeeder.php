<?php

namespace Database\Seeders;

use App\Modules\Inventory\Models\Inventory;
use App\Modules\Product\Models\Product;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $products = Product::where('tenant_id', $tenant->id)->get();

            foreach ($products as $product) {
                Inventory::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'product_id' => $product->id],
                    [
                        'tenant_id'          => $tenant->id,
                        'product_id'         => $product->id,
                        'warehouse_location' => 'WH-' . strtoupper(substr($product->category ?? 'GEN', 0, 3)) . '-' . rand(1, 50),
                        'quantity'           => rand(20, 500),
                        'reserved_quantity'  => 0,
                        'reorder_point'      => rand(5, 20),
                        'reorder_quantity'   => rand(50, 200),
                        'last_restocked_at'  => now()->subDays(rand(1, 30)),
                    ]
                );
            }
        }
    }
}
