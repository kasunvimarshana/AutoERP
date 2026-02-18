<?php

declare(strict_types=1);

namespace Modules\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tenancy\Entities\Tenant;

/**
 * Tenant Factory
 *
 * Generates realistic tenant data with domains, settings, and subscription info.
 */
class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = fake()->company();
        $slug = Str::slug($companyName);

        return [
            'name' => $companyName,
            'slug' => $slug,
            'domain' => $slug.'.'.config('app.domain', 'example.com'),
            'database' => 'tenant_'.$slug,
            'schema' => $slug,
            'status' => 'active',
            'settings' => [
                'timezone' => fake()->timezone(),
                'locale' => fake()->randomElement(['en', 'es', 'fr', 'de', 'pt']),
                'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'CAD', 'AUD']),
                'date_format' => fake()->randomElement(['Y-m-d', 'm/d/Y', 'd/m/Y']),
                'time_format' => fake()->randomElement(['H:i', 'h:i A']),
                'fiscal_year_start' => fake()->randomElement(['01-01', '04-01', '07-01', '10-01']),
                'company' => [
                    'name' => $companyName,
                    'email' => fake()->companyEmail(),
                    'phone' => fake()->phoneNumber(),
                    'website' => fake()->url(),
                    'tax_id' => fake()->bothify('??-#######'),
                    'registration_number' => fake()->bothify('REG-########'),
                ],
                'features' => [
                    'multi_currency' => fake()->boolean(70),
                    'multi_location' => fake()->boolean(50),
                    'advanced_reporting' => fake()->boolean(60),
                    'api_access' => fake()->boolean(80),
                    'custom_fields' => fake()->boolean(70),
                ],
                'notifications' => [
                    'email_enabled' => true,
                    'sms_enabled' => fake()->boolean(40),
                    'slack_enabled' => fake()->boolean(30),
                ],
            ],
            'features' => [
                'sales',
                'crm',
                'inventory',
                'accounting',
                'reporting',
            ],
            'limits' => [
                'users' => fake()->randomElement([5, 10, 25, 50, 100, null]),
                'storage_mb' => fake()->randomElement([1024, 5120, 10240, 51200, null]),
                'api_calls_per_hour' => fake()->randomElement([1000, 5000, 10000, null]),
                'monthly_invoices' => fake()->randomElement([100, 500, 1000, null]),
            ],
            'trial_ends_at' => null,
            'subscription_ends_at' => now()->addYear(),
        ];
    }

    /**
     * Indicate that the tenant is on trial.
     */
    public function onTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->addDays(fake()->numberBetween(7, 30)),
            'subscription_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the tenant has expired subscription.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_ends_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the tenant is a small business.
     */
    public function smallBusiness(): static
    {
        return $this->state(fn (array $attributes) => [
            'limits' => [
                'users' => 5,
                'storage_mb' => 1024,
                'api_calls_per_hour' => 1000,
                'monthly_invoices' => 100,
            ],
        ]);
    }

    /**
     * Indicate that the tenant is an enterprise.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'limits' => [
                'users' => null,
                'storage_mb' => null,
                'api_calls_per_hour' => null,
                'monthly_invoices' => null,
            ],
            'settings' => array_merge($attributes['settings'] ?? [], [
                'features' => [
                    'multi_currency' => true,
                    'multi_location' => true,
                    'advanced_reporting' => true,
                    'api_access' => true,
                    'custom_fields' => true,
                    'sso' => true,
                    'audit_logs' => true,
                ],
            ]),
        ]);
    }
}
