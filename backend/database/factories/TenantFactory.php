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
        return [
            'id' => Str::uuid(),
            'name' => $this->faker->company(),
            'subdomain' => $this->faker->unique()->slug(),
            'database_name' => null,
            'isolation_strategy' => 'row_level',
            'status' => 'active',
            'settings' => [],
            'metadata' => [],
            'trial_ends_at' => now()->addDays(14),
            'subscription_ends_at' => now()->addYear(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
