<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Tenant\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random tenant, or create one if none exist
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();

        $name = fake()->company();

        return [
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'name' => $name,
            'code' => strtoupper(fake()->unique()->lexify('ORG-???-###')),
            'type' => fake()->randomElement(['company', 'department', 'division', 'branch', 'team']),
            'metadata' => [
                'address' => fake()->address(),
                'phone' => fake()->phoneNumber(),
                'email' => fake()->companyEmail(),
            ],
            'level' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the organization is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific tenant for the organization.
     */
    public function forTenant(Tenant|string $tenant): static
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Set a parent organization.
     */
    public function withParent(Organization|string $parent): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            if ($parent instanceof Organization) {
                return [
                    'parent_id' => $parent->id,
                    'tenant_id' => $parent->tenant_id,
                    'level' => $parent->level + 1,
                ];
            }

            $parentOrg = Organization::findOrFail($parent);

            return [
                'parent_id' => $parentOrg->id,
                'tenant_id' => $parentOrg->tenant_id,
                'level' => $parentOrg->level + 1,
            ];
        });
    }

    /**
     * Create as a department.
     */
    public function asDepartment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'department',
        ]);
    }

    /**
     * Create as a branch.
     */
    public function asBranch(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'branch',
        ]);
    }
}
