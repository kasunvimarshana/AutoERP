<?php

namespace Database\Factories;

use App\Modules\CRM\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['individual', 'business']);
        
        return [
            'uuid' => Str::uuid(),
            'code' => 'CUST-' . fake()->unique()->numberBetween(100000, 999999),
            'type' => $type,
            'first_name' => $type === 'individual' ? fake()->firstName() : null,
            'last_name' => $type === 'individual' ? fake()->lastName() : null,
            'company_name' => $type === 'business' ? fake()->company() : null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'tax_number' => fake()->optional()->numerify('TAX-########'),
            'credit_limit' => fake()->randomFloat(2, 1000, 50000),
            'payment_terms' => fake()->randomElement([7, 15, 30, 60]),
            'status' => 'active',
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'individual',
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company_name' => null,
        ]);
    }

    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'business',
            'first_name' => null,
            'last_name' => null,
            'company_name' => fake()->company(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
