<?php

declare(strict_types=1);

namespace Modules\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;

/**
 * Vehicle Factory
 *
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $makes = ['Toyota', 'Honda', 'Ford', 'Chevrolet', 'BMW', 'Mercedes-Benz', 'Nissan', 'Volkswagen', 'Hyundai', 'Kia'];
        $make = $this->faker->randomElement($makes);

        $models = [
            'Toyota' => ['Camry', 'Corolla', 'RAV4', 'Highlander'],
            'Honda' => ['Accord', 'Civic', 'CR-V', 'Pilot'],
            'Ford' => ['F-150', 'Mustang', 'Explorer', 'Escape'],
            'Chevrolet' => ['Silverado', 'Malibu', 'Equinox', 'Tahoe'],
            'BMW' => ['3 Series', '5 Series', 'X3', 'X5'],
            'Mercedes-Benz' => ['C-Class', 'E-Class', 'GLC', 'GLE'],
            'Nissan' => ['Altima', 'Sentra', 'Rogue', 'Pathfinder'],
            'Volkswagen' => ['Jetta', 'Passat', 'Tiguan', 'Atlas'],
            'Hyundai' => ['Elantra', 'Sonata', 'Tucson', 'Santa Fe'],
            'Kia' => ['Forte', 'Optima', 'Sportage', 'Sorento'],
        ];

        $year = $this->faker->numberBetween(2010, date('Y'));
        $currentMileage = $this->faker->numberBetween(0, 200000);

        return [
            'customer_id' => Customer::factory(),
            'vehicle_number' => Vehicle::generateVehicleNumber(),
            'registration_number' => strtoupper($this->faker->bothify('???-####')),
            'vin' => strtoupper($this->faker->bothify('?#?#?#?#?########')),
            'make' => $make,
            'model' => $this->faker->randomElement($models[$make] ?? ['Model X']),
            'year' => $year,
            'color' => $this->faker->randomElement(['White', 'Black', 'Silver', 'Blue', 'Red', 'Gray', 'Green']),
            'engine_number' => strtoupper($this->faker->bothify('ENG-########')),
            'chassis_number' => strtoupper($this->faker->bothify('CHS-########')),
            'fuel_type' => $this->faker->randomElement(['petrol', 'diesel', 'electric', 'hybrid']),
            'transmission' => $this->faker->randomElement(['manual', 'automatic', 'cvt']),
            'current_mileage' => $currentMileage,
            'purchase_date' => $this->faker->dateTimeBetween("-{$year} years", 'now'),
            'registration_date' => $this->faker->dateTimeBetween("-{$year} years", 'now'),
            'insurance_expiry' => $this->faker->dateTimeBetween('now', '+2 years'),
            'insurance_provider' => $this->faker->company(),
            'insurance_policy_number' => strtoupper($this->faker->bothify('POL-########')),
            'status' => $this->faker->randomElement(['active', 'inactive', 'sold', 'scrapped']),
            'notes' => $this->faker->optional()->sentence(),
            'last_service_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'next_service_mileage' => $currentMileage + $this->faker->numberBetween(5000, 15000),
            'next_service_date' => $this->faker->dateTimeBetween('now', '+6 months'),
        ];
    }

    /**
     * Indicate that the vehicle is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the vehicle is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the vehicle is due for service.
     */
    public function dueForService(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_service_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'next_service_mileage' => $attributes['current_mileage'] ?? 50000,
        ]);
    }

    /**
     * Indicate that the vehicle has expiring insurance.
     */
    public function expiringInsurance(): static
    {
        return $this->state(fn (array $attributes) => [
            'insurance_expiry' => $this->faker->dateTimeBetween('now', '+30 days'),
        ]);
    }

    /**
     * Set a specific customer for the vehicle.
     */
    public function forCustomer(int $customerId): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customerId,
        ]);
    }
}
