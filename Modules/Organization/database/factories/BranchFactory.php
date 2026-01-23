<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Enums\BranchStatus;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Organization;

/**
 * Branch Factory
 *
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'branch_code' => 'BR'.date('ymd').fake()->unique()->numberBetween(100, 999),
            'name' => fake()->city().' Branch',
            'status' => BranchStatus::ACTIVE->value,
            'manager_name' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->countryCode(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'operating_hours' => [
                'monday' => ['open' => '08:00', 'close' => '18:00'],
                'tuesday' => ['open' => '08:00', 'close' => '18:00'],
                'wednesday' => ['open' => '08:00', 'close' => '18:00'],
                'thursday' => ['open' => '08:00', 'close' => '18:00'],
                'friday' => ['open' => '08:00', 'close' => '18:00'],
                'saturday' => ['open' => '09:00', 'close' => '14:00'],
                'sunday' => ['open' => null, 'close' => null],
            ],
            'services_offered' => [
                'oil_change',
                'tire_rotation',
                'brake_service',
                'engine_diagnostic',
                'transmission_service',
            ],
            'capacity_vehicles' => fake()->numberBetween(10, 50),
            'bay_count' => fake()->numberBetween(2, 10),
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the branch is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BranchStatus::INACTIVE->value,
        ]);
    }

    /**
     * Indicate that the branch is under maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BranchStatus::MAINTENANCE->value,
        ]);
    }

    /**
     * Indicate that the branch belongs to a specific organization.
     */
    public function forOrganization(Organization|int $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization instanceof Organization ? $organization->id : $organization,
        ]);
    }
}
