<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Tenant;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'domain' => $this->faker->unique()->domainName(),
            'status' => 'active',
            'plan' => $this->faker->randomElement(['free', 'basic', 'premium', 'enterprise']),
            'settings' => [],
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function suspended(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function withTrial(): self
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function withSubscription(): self
    {
        return $this->state(fn (array $attributes) => [
            'subscription_ends_at' => now()->addMonths(1),
        ]);
    }
}
