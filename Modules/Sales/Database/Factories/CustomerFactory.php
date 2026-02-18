<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Entities\Customer;

/**
 * Customer Factory
 *
 * Generates realistic customer data with company names, emails, phone, credit limits.
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = fake()->company();
        $isCompany = fake()->boolean(70);

        return [
            'tenant_id' => 1,
            'customer_number' => 'CUST-'.fake()->unique()->numerify('######'),
            'name' => $isCompany ? $companyName : fake()->name(),
            'legal_name' => $isCompany ? $companyName.' '.fake()->companySuffix() : null,
            'type' => $isCompany ? 'company' : 'individual',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->boolean(60) ? fake()->phoneNumber() : null,
            'website' => $isCompany && fake()->boolean(70) ? fake()->url() : null,
            'tax_number' => $isCompany ? fake()->bothify('##-#######') : null,
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'CAD', 'AUD']),
            'payment_terms' => fake()->randomElement(['immediate', 'net_15', 'net_30', 'net_45', 'net_60']),
            'credit_limit' => fake()->boolean(80) ? fake()->randomFloat(2, 5000, 100000) : null,
            'status' => 'active',
            'tags' => fake()->boolean(50) ? fake()->randomElements(
                ['vip', 'wholesale', 'retail', 'distributor', 'partner', 'new', 'high-value'],
                fake()->numberBetween(1, 3)
            ) : [],
            'notes' => fake()->boolean(40) ? fake()->paragraph() : null,
        ];
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the customer is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the customer is a VIP.
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => fake()->randomFloat(2, 100000, 500000),
            'payment_terms' => 'net_60',
            'tags' => array_unique(array_merge($attributes['tags'] ?? [], ['vip', 'high-value'])),
        ]);
    }

    /**
     * Indicate that the customer is a company.
     */
    public function company(): static
    {
        $companyName = fake()->company();

        return $this->state(fn (array $attributes) => [
            'name' => $companyName,
            'legal_name' => $companyName.' '.fake()->companySuffix(),
            'type' => 'company',
            'website' => fake()->url(),
            'tax_number' => fake()->bothify('##-#######'),
        ]);
    }

    /**
     * Indicate that the customer is an individual.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name(),
            'legal_name' => null,
            'type' => 'individual',
            'website' => null,
            'tax_number' => null,
        ]);
    }

    /**
     * Indicate that the customer is a wholesale customer.
     */
    public function wholesale(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'company',
            'credit_limit' => fake()->randomFloat(2, 50000, 250000),
            'payment_terms' => fake()->randomElement(['net_30', 'net_45', 'net_60']),
            'tags' => array_unique(array_merge($attributes['tags'] ?? [], ['wholesale', 'bulk-buyer'])),
        ]);
    }

    /**
     * Indicate that the customer has no credit limit.
     */
    public function noCreditLimit(): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => null,
        ]);
    }
}
