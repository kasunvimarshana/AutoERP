<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sales\Entities\Customer;

/**
 * Customer Seeder
 *
 * Seeds customer data for testing and development.
 */
class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding customers...');

        // Create VIP customers
        Customer::factory()->vip()->count(3)->create([
            'tenant_id' => 1,
        ]);

        // Create wholesale customers
        Customer::factory()->wholesale()->count(5)->create([
            'tenant_id' => 1,
        ]);

        // Create company customers
        Customer::factory()->company()->count(10)->create([
            'tenant_id' => 1,
        ]);

        // Create individual customers
        Customer::factory()->individual()->count(8)->create([
            'tenant_id' => 1,
        ]);

        // Create inactive customers
        Customer::factory()->inactive()->count(2)->create([
            'tenant_id' => 1,
        ]);

        // Create suspended customer
        Customer::factory()->suspended()->count(1)->create([
            'tenant_id' => 1,
        ]);

        // Create additional random customers
        Customer::factory()->count(5)->create([
            'tenant_id' => 1,
        ]);

        $this->command->info('Customers seeded successfully!');
    }
}
