<?php

declare(strict_types=1);

namespace Modules\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Customer\Models\Customer;

/**
 * Customer Factory
 *
 * @extends Factory<Customer>
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
        $customerType = $this->faker->randomElement(['individual', 'business']);
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        return [
            'customer_number' => Customer::generateCustomerNumber(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->phoneNumber(),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional()->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'notes' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'blocked']),
            'customer_type' => $customerType,
            'company_name' => $customerType === 'business' ? $this->faker->company() : null,
            'tax_id' => $customerType === 'business' ? $this->faker->numerify('TAX-########') : null,
            'receive_notifications' => $this->faker->boolean(80),
            'receive_marketing' => $this->faker->boolean(40),
            'last_service_date' => $this->faker->optional()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    /**
     * Indicate that the customer is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
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
     * Indicate that the customer is an individual.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'individual',
            'company_name' => null,
            'tax_id' => null,
        ]);
    }

    /**
     * Indicate that the customer is a business.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'business',
            'company_name' => $this->faker->company(),
            'tax_id' => $this->faker->numerify('TAX-########'),
        ]);
    }

    /**
     * Indicate that the customer receives notifications.
     */
    public function withNotifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'receive_notifications' => true,
            'receive_marketing' => true,
        ]);
    }
}
