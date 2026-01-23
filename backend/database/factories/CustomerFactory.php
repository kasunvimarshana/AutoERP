<?php

namespace Database\Factories;

use App\Modules\CustomerManagement\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_type' => $this->faker->randomElement(['individual', 'business']),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'company_name' => $this->faker->optional()->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->optional()->phoneNumber(),
            'date_of_birth' => $this->faker->optional()->date(),
            'address_line1' => $this->faker->optional()->streetAddress(),
            'city' => $this->faker->optional()->city(),
            'state' => $this->faker->optional()->state(),
            'postal_code' => $this->faker->optional()->postcode(),
            'country' => $this->faker->countryCode(),
            'status' => 'active',
            'credit_limit' => $this->faker->randomFloat(2, 0, 10000),
            'payment_terms_days' => $this->faker->randomElement([15, 30, 60, 90]),
            'preferred_language' => 'en',
        ];
    }
}
