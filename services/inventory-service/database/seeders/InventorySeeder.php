<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'product_id'         => 1,
                'quantity'           => 150,
                'reserved_quantity'  => 10,
                'warehouse_location' => 'A-01-01',
                'reorder_level'      => 20,
                'reorder_quantity'   => 100,
                'unit_cost'          => 79.9900,
                'status'             => 'active',
                'last_counted_at'    => now(),
            ],
            [
                'product_id'         => 2,
                'quantity'           => 45,
                'reserved_quantity'  => 5,
                'warehouse_location' => 'B-02-03',
                'reorder_level'      => 10,
                'reorder_quantity'   => 50,
                'unit_cost'          => 199.9900,
                'status'             => 'active',
                'last_counted_at'    => now(),
            ],
            [
                'product_id'         => 3,
                'quantity'           => 300,
                'reserved_quantity'  => 0,
                'warehouse_location' => 'C-01-05',
                'reorder_level'      => 50,
                'reorder_quantity'   => 200,
                'unit_cost'          => 12.5000,
                'status'             => 'active',
                'last_counted_at'    => now(),
            ],
            [
                'product_id'         => 4,
                'quantity'           => 80,
                'reserved_quantity'  => 20,
                'warehouse_location' => 'A-03-02',
                'reorder_level'      => 15,
                'reorder_quantity'   => 75,
                'unit_cost'          => 55.0000,
                'status'             => 'active',
                'last_counted_at'    => now(),
            ],
            [
                'product_id'         => 5,
                'quantity'           => 0,
                'reserved_quantity'  => 0,
                'warehouse_location' => 'D-01-01',
                'reorder_level'      => 10,
                'reorder_quantity'   => 50,
                'unit_cost'          => 65.0000,
                'status'             => 'active',
                'last_counted_at'    => now(),
            ],
            [
                'product_id'         => 6,
                'quantity'           => 200,
                'reserved_quantity'  => 0,
                'warehouse_location' => 'E-02-04',
                'reorder_level'      => 30,
                'reorder_quantity'   => 150,
                'unit_cost'          => 18.9900,
                'status'             => 'inactive',
                'last_counted_at'    => now()->subDays(30),
            ],
            [
                'product_id'         => 7,
                'quantity'           => 120,
                'reserved_quantity'  => 15,
                'warehouse_location' => 'A-02-06',
                'reorder_level'      => 25,
                'reorder_quantity'   => 100,
                'unit_cost'          => 32.5000,
                'status'             => 'active',
                'last_counted_at'    => now(),
            ],
            [
                'product_id'         => 8,
                'quantity'           => 60,
                'reserved_quantity'  => 8,
                'warehouse_location' => 'C-03-01',
                'reorder_level'      => 15,
                'reorder_quantity'   => 60,
                'unit_cost'          => 42.0000,
                'status'             => 'active',
                'last_counted_at'    => now(),
            ],
        ];

        foreach ($items as $itemData) {
            $inventory = Inventory::updateOrCreate(
                ['product_id' => $itemData['product_id']],
                $itemData
            );

            // Create an initial receipt transaction for seeded inventory
            if ($inventory->wasRecentlyCreated && $inventory->quantity > 0) {
                InventoryTransaction::create([
                    'inventory_id'      => $inventory->id,
                    'product_id'        => $inventory->product_id,
                    'type'              => InventoryTransaction::TYPE_RECEIPT,
                    'quantity'          => $inventory->quantity,
                    'previous_quantity' => 0,
                    'new_quantity'      => $inventory->quantity,
                    'notes'             => 'Initial stock from database seeder.',
                    'performed_by'      => 'seeder',
                ]);
            }
        }

        $this->command->info('Inventory seeded successfully.');
    }
}
