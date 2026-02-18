<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Entities\Location;

class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1, // Will be set by test
            'organization_id' => null, // Must be set
            'parent_location_id' => null,
            'code' => strtoupper($this->faker->unique()->bothify('LOC-###')),
            'name' => [
                'en' => $this->faker->city().' '.$this->faker->randomElement(['Office', 'Branch', 'Warehouse']),
                'es' => $this->faker->city().' '.$this->faker->randomElement(['Oficina', 'Sucursal', 'Almacén']),
            ],
            'description' => [
                'en' => $this->faker->optional()->sentence(),
            ],
            'location_type' => $this->faker->randomElement([
                'headquarters',
                'office',
                'branch',
                'warehouse',
                'factory',
                'retail',
                'distribution_center',
                'transit',
                'virtual',
                'other',
            ]),
            'status' => $this->faker->randomElement(['active', 'inactive', 'under_construction', 'closed']),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'fax' => $this->faker->optional()->phoneNumber(),
            'contact_person' => $this->faker->name(),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => $this->faker->optional()->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->countryCode(),
            'latitude' => $this->faker->optional()->latitude(),
            'longitude' => $this->faker->optional()->longitude(),
            'operating_hours' => [
                'monday' => ['open' => '09:00', 'close' => '17:00'],
                'tuesday' => ['open' => '09:00', 'close' => '17:00'],
                'wednesday' => ['open' => '09:00', 'close' => '17:00'],
                'thursday' => ['open' => '09:00', 'close' => '17:00'],
                'friday' => ['open' => '09:00', 'close' => '17:00'],
            ],
            'timezone' => $this->faker->timezone(),
            'area_sqm' => $this->faker->optional()->numberBetween(100, 10000),
            'capacity' => $this->faker->optional()->numberBetween(10, 500),
            'settings' => [
                'parking_available' => $this->faker->boolean(),
                'wheelchair_accessible' => $this->faker->boolean(),
            ],
            'features' => ['security', 'wifi'],
            'metadata' => [],
            'level' => 0,
            'path' => null, // Will be set by model
        ];
    }

    /**
     * Indicate that the location is a warehouse.
     */
    public function warehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => 'warehouse',
            'capacity' => $this->faker->numberBetween(1000, 100000),
        ]);
    }

    /**
     * Indicate that the location is an office.
     */
    public function office(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => 'office',
        ]);
    }

    /**
     * Indicate that the location is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}
