<?php

namespace Database\Factories\CRM;

use App\Models\Tenant;
use App\Modules\CRM\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\CRM\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_type' => fake()->randomElement(['individual', 'business']),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->phoneNumber(),
            'company_name' => fake()->optional()->company(),
            'tax_number' => fake()->optional()->numerify('TAX-########'),
            'billing_address' => fake()->streetAddress(),
            'billing_city' => fake()->city(),
            'billing_state' => fake()->state(),
            'billing_country' => fake()->country(),
            'billing_postal_code' => fake()->postcode(),
            'shipping_address' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_state' => fake()->state(),
            'shipping_country' => fake()->country(),
            'shipping_postal_code' => fake()->postcode(),
            'credit_limit' => fake()->randomFloat(2, 0, 50000),
            'payment_terms_days' => fake()->randomElement([0, 7, 15, 30, 45, 60]),
            'discount_percentage' => fake()->randomFloat(2, 0, 15),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the customer is a business.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'business',
            'company_name' => fake()->company(),
            'tax_number' => 'TAX-'.fake()->numerify('########'),
        ]);
    }

    /**
     * Indicate that the customer is an individual.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'individual',
            'company_name' => null,
        ]);
    }

    /**
     * Set specific tenant for the customer.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
