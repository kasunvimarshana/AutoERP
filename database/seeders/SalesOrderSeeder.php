<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Entities\SalesOrder;
use Modules\Sales\Entities\SalesOrderLine;

/**
 * Sales Order Seeder
 *
 * Seeds sales orders with order lines for testing and development.
 */
class SalesOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding sales orders...');

        // Get customers to assign orders to
        $customers = Customer::where('tenant_id', 1)->where('status', 'active')->get();

        if ($customers->isEmpty()) {
            $this->command->warn('No active customers found. Please run CustomerSeeder first.');

            return;
        }

        DB::beginTransaction();
        try {
            // Create draft orders
            $this->createOrdersWithLines($customers, 'draft', 2);

            // Create pending orders
            $this->createOrdersWithLines($customers, 'pending', 3);

            // Create confirmed orders
            $this->createOrdersWithLines($customers, 'confirmed', 4);

            // Create shipped orders
            $this->createOrdersWithLines($customers, 'shipped', 3);

            // Create delivered and paid orders
            $this->createOrdersWithLines($customers, 'delivered', 3);

            // Create large order
            $largeOrder = SalesOrder::factory()
                ->large()
                ->confirmed()
                ->paid()
                ->create([
                    'tenant_id' => 1,
                    'customer_id' => $customers->random()->id,
                ]);

            // Add multiple lines to large order
            SalesOrderLine::factory()->count(8)->create([
                'tenant_id' => 1,
                'sales_order_id' => $largeOrder->id,
            ]);

            $largeOrder->calculateTotals();

            // Create cancelled orders
            $this->createOrdersWithLines($customers, 'cancelled', 1);

            DB::commit();
            $this->command->info('Sales orders seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding sales orders: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Create orders with lines for a specific status.
     *
     * @param  \Illuminate\Support\Collection  $customers
     */
    private function createOrdersWithLines($customers, string $status, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $order = SalesOrder::factory()
                ->{$status}()
                ->create([
                    'tenant_id' => 1,
                    'customer_id' => $customers->random()->id,
                ]);

            // Add random number of lines (2-6)
            $lineCount = fake()->numberBetween(2, 6);
            SalesOrderLine::factory()->count($lineCount)->create([
                'tenant_id' => 1,
                'sales_order_id' => $order->id,
            ]);

            // Recalculate totals based on lines
            $order->calculateTotals();
        }
    }
}
