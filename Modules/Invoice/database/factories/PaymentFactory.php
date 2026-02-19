<?php

declare(strict_types=1);

namespace Modules\Invoice\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Invoice\Models\Payment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Invoice\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_number' => Payment::generatePaymentNumber(),
            'payment_date' => $this->faker->date(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'payment_method' => $this->faker->randomElement(['cash', 'credit_card', 'debit_card', 'bank_transfer']),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'reference_number' => $this->faker->optional()->numerify('REF-########'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
