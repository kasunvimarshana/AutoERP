<?php

declare(strict_types=1);

namespace Modules\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Customer\Models\VehicleServiceRecord;

/**
 * Customer Database Seeder
 *
 * Seeds the database with sample customer and vehicle data for testing
 */
class CustomerDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 individual customers with 1-3 vehicles each
        Customer::factory()
            ->count(50)
            ->individual()
            ->active()
            ->has(Vehicle::factory()->count(rand(1, 3))->active())
            ->create();

        // Create 20 business customers with 2-5 vehicles each
        Customer::factory()
            ->count(20)
            ->business()
            ->active()
            ->has(Vehicle::factory()->count(rand(2, 5))->active())
            ->create();

        // Create 10 customers with vehicles due for service
        Customer::factory()
            ->count(10)
            ->active()
            ->withNotifications()
            ->has(Vehicle::factory()->count(2)->dueForService())
            ->create();

        // Create 5 customers with vehicles that have expiring insurance
        Customer::factory()
            ->count(5)
            ->active()
            ->withNotifications()
            ->has(Vehicle::factory()->count(1)->expiringInsurance())
            ->create();

        // Add service records to existing vehicles
        $this->seedServiceRecords();

        $this->command->info('Customer module seeded successfully!');
        $this->command->info('Created customers with their vehicles and service records');
    }

    /**
     * Seed service records for existing vehicles
     */
    protected function seedServiceRecords(): void
    {
        $vehicles = Vehicle::with('customer')->get();

        foreach ($vehicles as $vehicle) {
            // Create 2-8 historical service records per vehicle
            $serviceCount = rand(2, 8);

            // Create multiple branch service records to demonstrate cross-branch tracking
            $branches = ['BRANCH-1', 'BRANCH-2', 'BRANCH-3', 'BRANCH-4', 'BRANCH-5'];

            for ($i = 0; $i < $serviceCount; $i++) {
                VehicleServiceRecord::factory()
                    ->completed()
                    ->forVehicle($vehicle)
                    ->forBranch($branches[array_rand($branches)])
                    ->create([
                        'service_date' => now()->subDays(rand(30, 730)), // Random date in last 2 years
                    ]);
            }

            // Add 1-2 pending services for some vehicles
            if (rand(0, 1)) {
                VehicleServiceRecord::factory()
                    ->pending()
                    ->forVehicle($vehicle)
                    ->forBranch($branches[array_rand($branches)])
                    ->create();
            }

            // Add in-progress service for some vehicles
            if (rand(0, 4) === 0) { // 20% chance
                VehicleServiceRecord::factory()
                    ->inProgress()
                    ->forVehicle($vehicle)
                    ->forBranch($branches[array_rand($branches)])
                    ->create();
            }
        }

        $this->command->info('Created service records with cross-branch history');
    }
}
