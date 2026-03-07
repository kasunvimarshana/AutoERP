<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'tenant_id'   => Tenant::factory(),
            'keycloak_id' => Str::uuid()->toString(),
            'name'        => $this->faker->name(),
            'email'       => $this->faker->unique()->safeEmail(),
            'username'    => $this->faker->unique()->userName(),
            'role'        => $this->faker->randomElement(['viewer', 'staff', 'manager', 'admin']),
            'status'      => 'active',
            'profile'     => [],
            'permissions' => [],
            'metadata'    => [],
            'last_login_at' => $this->faker->optional()->dateTimeBetween('-30 days'),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attrs) => ['role' => 'admin']);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attrs) => ['role' => 'manager']);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'inactive']);
    }
}
