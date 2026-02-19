<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Auth\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random tenant, or create one if none exist
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();

        // Get a random organization for the tenant, or create one if none exist
        $organization = Organization::where('tenant_id', $tenant->id)
            ->inRandomOrder()
            ->first()
            ?? Organization::factory()->create(['tenant_id' => $tenant->id]);

        return [
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'metadata' => [
                'phone' => fake()->phoneNumber(),
                'position' => fake()->jobTitle(),
            ],
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific tenant for the user.
     */
    public function forTenant(Tenant|string $tenant): static
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $this->state(function (array $attributes) use ($tenantId) {
            // Get organization for tenant
            $organization = Organization::where('tenant_id', $tenantId)
                ->inRandomOrder()
                ->first()
                ?? Organization::factory()->create(['tenant_id' => $tenantId]);

            return [
                'tenant_id' => $tenantId,
                'organization_id' => $organization->id,
            ];
        });
    }

    /**
     * Set a specific organization for the user.
     */
    public function forOrganization(Organization|string $organization): static
    {
        return $this->state(function (array $attributes) use ($organization) {
            if ($organization instanceof Organization) {
                return [
                    'tenant_id' => $organization->tenant_id,
                    'organization_id' => $organization->id,
                ];
            }

            $org = Organization::findOrFail($organization);

            return [
                'tenant_id' => $org->tenant_id,
                'organization_id' => $org->id,
            ];
        });
    }
}
