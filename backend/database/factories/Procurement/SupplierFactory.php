<?php

namespace Database\Factories\Procurement;

use App\Models\Tenant;
use App\Modules\Procurement\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Procurement\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->phoneNumber(),
            'company_name' => fake()->company(),
            'tax_number' => 'TAX-'.fake()->numerify('########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'payment_terms_days' => fake()->randomElement([7, 15, 30, 45, 60]),
            'credit_limit' => fake()->randomFloat(2, 0, 100000),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the supplier is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set specific tenant for the supplier.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
