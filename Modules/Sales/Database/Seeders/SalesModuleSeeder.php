<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Entities\Lead;
use Modules\Sales\Entities\SalesOrder;
use Modules\Sales\Entities\SalesOrderLine;

/**
 * Sales Module Seeder
 *
 * Seeds the database with sample sales data for testing and development.
 */
class SalesModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Sales module data...');

        $customers = Customer::factory()->count(50)->create();
        $this->command->info('Created 50 customers');

        Lead::factory()->count(30)->create(['status' => 'active', 'stage' => 'prospect']);
        Lead::factory()->count(20)->create(['status' => 'active', 'stage' => 'qualified']);
        Lead::factory()->count(15)->create(['status' => 'active', 'stage' => 'proposal']);
        Lead::factory()->count(10)->create(['status' => 'won', 'stage' => 'customer']);
        Lead::factory()->count(5)->create(['status' => 'lost']);
        $this->command->info('Created 80 leads');

        foreach ($customers->take(30) as $customer) {
            $orderCount = rand(1, 3);
            for ($i = 0; $i < $orderCount; $i++) {
                $order = SalesOrder::factory()->create([
                    'customer_id' => $customer->id,
                    'tenant_id' => $customer->tenant_id,
                ]);
                SalesOrderLine::factory()->count(rand(2, 5))->create([
                    'sales_order_id' => $order->id,
                    'tenant_id' => $customer->tenant_id,
                ]);
            }
        }

        $this->command->info('Sales module seeding completed!');
    }
}
