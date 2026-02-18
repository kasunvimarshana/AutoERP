<?php

namespace Database\Factories\IAM;

use App\Models\Tenant;
use App\Modules\IAM\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\IAM\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_system_role' => false,
        ];
    }

    /**
     * Indicate that the role is a system role.
     */
    public function systemRole(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system_role' => true,
        ]);
    }

    /**
     * Create a super admin role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Super administrator with all permissions',
            'is_system_role' => true,
        ]);
    }

    /**
     * Create an admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrator with most permissions',
            'is_system_role' => true,
        ]);
    }

    /**
     * Create a user role.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Regular user with basic permissions',
            'is_system_role' => true,
        ]);
    }

    /**
     * Set specific tenant for the role.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
