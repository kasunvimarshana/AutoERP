<?php

namespace Database\Factories\Billing;

use App\Modules\Billing\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'invoice_id' => 1,
            'payment_number' => 'PAY-' . strtoupper($this->faker->unique()->bothify('####??####')),
            'payment_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'bank_transfer', 'digital']),
            'reference_number' => $this->faker->optional()->bothify('REF-##########'),
            'notes' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
        ]);
    }
}
