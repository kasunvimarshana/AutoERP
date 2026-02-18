<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'domain' => fake()->unique()->domainName(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'logo' => null,
            'settings' => [
                'currency' => 'USD',
                'timezone' => 'UTC',
                'date_format' => 'Y-m-d',
            ],
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
            'subscription_ends_at' => now()->addYear(),
        ];
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the tenant is in trial.
     */
    public function inTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->addDays(15),
            'subscription_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant's trial has expired.
     */
    public function trialExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->subDays(5),
            'subscription_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant has an active subscription.
     */
    public function subscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => null,
            'subscription_ends_at' => now()->addYear(),
        ]);
    }

    /**
     * Indicate that the tenant's subscription has expired.
     */
    public function subscriptionExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => null,
            'subscription_ends_at' => now()->subDays(10),
        ]);
    }
}
