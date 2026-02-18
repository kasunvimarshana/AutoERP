<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Entities\Payment;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'payment_number' => 'PAY-'.$this->faker->unique()->numerify('######'),
            'customer_id' => 1,
            'payment_date' => $this->faker->date(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'currency' => 'USD',
            'exchange_rate' => 1,
            'payment_method' => $this->faker->randomElement(['cash', 'check', 'bank_transfer', 'credit_card']),
            'reference' => $this->faker->word(),
            'status' => 'pending',
            'tags' => [],
        ];
    }
}
