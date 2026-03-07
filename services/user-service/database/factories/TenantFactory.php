<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name'      => $name,
            'slug'      => Str::slug($name).'-'.Str::random(4),
            'domain'    => $this->faker->optional()->domainName(),
            'plan'      => $this->faker->randomElement(['free', 'starter', 'professional', 'enterprise']),
            'status'    => 'active',
            'max_users' => $this->faker->optional()->numberBetween(5, 500),
            'settings'  => [],
            'metadata'  => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'inactive']);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attrs) => ['status' => 'suspended']);
    }
}
