<?php

declare(strict_types=1);

namespace Modules\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Customer\Enums\ServiceStatus;
use Modules\Customer\Enums\ServiceType;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Customer\Models\VehicleServiceRecord;

/**
 * Vehicle Service Record Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Customer\Models\VehicleServiceRecord>
 */
class VehicleServiceRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VehicleServiceRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceDate = fake()->dateTimeBetween('-2 years', 'now');
        $mileage = fake()->numberBetween(10000, 200000);
        $laborCost = fake()->randomFloat(2, 50, 500);
        $partsCost = fake()->randomFloat(2, 0, 1000);

        return [
            'vehicle_id' => Vehicle::factory(),
            'customer_id' => Customer::factory(),
            'service_number' => VehicleServiceRecord::generateServiceNumber(),
            'branch_id' => 'BRANCH-'.fake()->numberBetween(1, 5),
            'service_date' => $serviceDate,
            'mileage_at_service' => $mileage,
            'service_type' => fake()->randomElement(ServiceType::values()),
            'service_description' => fake()->sentence(10),
            'parts_used' => json_encode([
                [
                    'name' => fake()->word(),
                    'quantity' => fake()->numberBetween(1, 4),
                    'price' => fake()->randomFloat(2, 10, 200),
                ],
            ]),
            'labor_cost' => $laborCost,
            'parts_cost' => $partsCost,
            'total_cost' => $laborCost + $partsCost,
            'technician_name' => fake()->name(),
            'technician_id' => null,
            'notes' => fake()->optional()->paragraph(),
            'next_service_mileage' => $mileage + fake()->numberBetween(5000, 10000),
            'next_service_date' => fake()->dateTimeBetween($serviceDate, '+6 months'),
            'status' => ServiceStatus::COMPLETED->value,
        ];
    }

    /**
     * State for pending service record
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceStatus::PENDING->value,
            'service_date' => fake()->dateTimeBetween('now', '+1 week'),
        ]);
    }

    /**
     * State for in-progress service record
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceStatus::IN_PROGRESS->value,
            'service_date' => now(),
        ]);
    }

    /**
     * State for completed service record
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceStatus::COMPLETED->value,
        ]);
    }

    /**
     * State for cancelled service record
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceStatus::CANCELLED->value,
            'notes' => 'Cancelled: '.fake()->sentence(),
        ]);
    }

    /**
     * State for regular service
     */
    public function regular(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::REGULAR->value,
            'service_description' => 'Regular maintenance service',
        ]);
    }

    /**
     * State for major service
     */
    public function major(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::MAJOR->value,
            'service_description' => 'Major service including comprehensive checks',
            'labor_cost' => fake()->randomFloat(2, 200, 800),
            'parts_cost' => fake()->randomFloat(2, 300, 1500),
        ]);
    }

    /**
     * State for repair service
     */
    public function repair(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::REPAIR->value,
            'service_description' => 'Repair: '.fake()->sentence(),
        ]);
    }

    /**
     * State for emergency service
     */
    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::EMERGENCY->value,
            'service_description' => 'Emergency repair: '.fake()->sentence(),
            'service_date' => now(),
        ]);
    }

    /**
     * State for inspection
     */
    public function inspection(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::INSPECTION->value,
            'service_description' => 'Vehicle inspection',
            'parts_cost' => 0,
            'labor_cost' => fake()->randomFloat(2, 30, 100),
        ]);
    }

    /**
     * State for warranty service
     */
    public function warranty(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::WARRANTY->value,
            'service_description' => 'Warranty service',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
        ]);
    }

    /**
     * State with specific branch
     */
    public function forBranch(string $branchId): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => $branchId,
        ]);
    }

    /**
     * State for a specific vehicle
     */
    public function forVehicle(Vehicle $vehicle): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_id' => $vehicle->id,
            'customer_id' => $vehicle->customer_id,
            'mileage_at_service' => $vehicle->current_mileage + fake()->numberBetween(100, 5000),
        ]);
    }
}
