<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'tenant_id'    => 1,
            'customer_id'  => 'cust-' . $this->faker->uuid(),
            'status'       => Order::STATUS_PENDING,
            'total_amount' => $this->faker->randomFloat(2, 5, 500),
            'currency'     => 'USD',
            'items'        => [
                [
                    'product_id' => 'prod-' . $this->faker->uuid(),
                    'quantity'   => $this->faker->numberBetween(1, 5),
                    'unit_price' => $this->faker->randomFloat(2, 5, 100),
                ],
            ],
            'metadata' => [],
            'saga_id'  => (string) Str::uuid(),
        ];
    }

    public function confirmed(): self
    {
        return $this->state(['status' => Order::STATUS_CONFIRMED]);
    }

    public function cancelled(): self
    {
        return $this->state(['status' => Order::STATUS_CANCELLED]);
    }

    public function failed(): self
    {
        return $this->state(['status' => Order::STATUS_FAILED]);
    }
}
