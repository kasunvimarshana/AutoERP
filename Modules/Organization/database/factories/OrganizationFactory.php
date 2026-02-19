<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Enums\OrganizationStatus;
use Modules\Organization\Enums\OrganizationType;
use Modules\Organization\Models\Organization;

/**
 * Organization Factory
 *
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_number' => 'ORG'.date('Ymd').fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->company(),
            'legal_name' => fake()->company().' Ltd.',
            'type' => fake()->randomElement(OrganizationType::values()),
            'status' => OrganizationStatus::ACTIVE->value,
            'tax_id' => fake()->unique()->numerify('TAX-########'),
            'registration_number' => fake()->unique()->numerify('REG-########'),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->countryCode(),
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the organization is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrganizationStatus::INACTIVE->value,
        ]);
    }

    /**
     * Indicate that the organization is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrganizationStatus::SUSPENDED->value,
        ]);
    }

    /**
     * Indicate that the organization is single branch type.
     */
    public function single(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => OrganizationType::SINGLE->value,
        ]);
    }

    /**
     * Indicate that the organization is multi-branch type.
     */
    public function multiBranch(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => OrganizationType::MULTI_BRANCH->value,
        ]);
    }

    /**
     * Indicate that the organization is franchise type.
     */
    public function franchise(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => OrganizationType::FRANCHISE->value,
        ]);
    }
}
