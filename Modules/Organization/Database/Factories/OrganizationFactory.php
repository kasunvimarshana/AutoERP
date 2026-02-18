<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Entities\Organization;

class OrganizationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1, // Will be set by test
            'parent_id' => null,
            'code' => strtoupper($this->faker->unique()->bothify('ORG-###')),
            'name' => [
                'en' => $this->faker->company(),
                'es' => $this->faker->company(),
            ],
            'legal_name' => $this->faker->company().' Inc.',
            'tax_id' => $this->faker->numerify('TAX-########'),
            'registration_number' => $this->faker->numerify('REG-########'),
            'organization_type' => $this->faker->randomElement([
                'headquarters',
                'subsidiary',
                'branch',
                'division',
                'other',
            ]),
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'fax' => $this->faker->optional()->phoneNumber(),
            'website' => $this->faker->optional()->url(),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => $this->faker->optional()->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->countryCode(),
            'latitude' => $this->faker->optional()->latitude(),
            'longitude' => $this->faker->optional()->longitude(),
            'settings' => [
                'default_currency' => 'USD',
                'timezone' => 'UTC',
            ],
            'features' => ['reporting', 'analytics'],
            'metadata' => [
                'industry' => $this->faker->randomElement(['Technology', 'Finance', 'Healthcare', 'Retail']),
            ],
            'level' => 0,
            'path' => null, // Will be set by model
        ];
    }

    /**
     * Indicate that the organization is a headquarters.
     */
    public function headquarters(): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_type' => 'headquarters',
            'parent_id' => null,
        ]);
    }

    /**
     * Indicate that the organization is a subsidiary.
     */
    public function subsidiary(): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_type' => 'subsidiary',
        ]);
    }

    /**
     * Indicate that the organization is a branch.
     */
    public function branch(): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_type' => 'branch',
        ]);
    }

    /**
     * Indicate that the organization is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the organization is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
