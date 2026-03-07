<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\SagaTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SagaTransaction>
 */
class SagaTransactionFactory extends Factory
{
    protected $model = SagaTransaction::class;

    public function definition(): array
    {
        return [
            'order_id'   => Order::factory(),
            'saga_id'    => (string) \Illuminate\Support\Str::uuid(),
            'step'       => $this->faker->randomElement(SagaTransaction::STEPS),
            'status'     => SagaTransaction::STATUS_PENDING,
            'payload'    => [],
            'result'     => null,
            'started_at' => now(),
        ];
    }

    public function completed(): self
    {
        return $this->state([
            'status'       => SagaTransaction::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state([
            'status'        => SagaTransaction::STATUS_FAILED,
            'error_message' => 'Test failure',
            'completed_at'  => now(),
        ]);
    }
}
