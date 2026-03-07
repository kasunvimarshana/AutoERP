<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(Order::STATUSES);

        return [
            'order_number'    => 'ORD-' . strtoupper(Str::random(4)) . '-' . now()->format('YmdHis') . $this->faker->numberBetween(1, 999),
            'customer_id'     => $this->faker->uuid(),
            'customer_name'   => $this->faker->name(),
            'customer_email'  => $this->faker->safeEmail(),
            'status'          => $status,
            'total_amount'    => $this->faker->randomFloat(2, 10, 500),
            'tax_amount'      => $this->faker->randomFloat(2, 0, 50),
            'discount_amount' => $this->faker->randomFloat(2, 0, 20),
            'shipping_address' => [
                'street'      => $this->faker->streetAddress(),
                'city'        => $this->faker->city(),
                'state'       => $this->faker->state(),
                'postal_code' => $this->faker->postcode(),
                'country'     => $this->faker->country(),
            ],
            'billing_address' => null,
            'notes'           => $this->faker->optional()->sentence(),
            'saga_status'     => Order::SAGA_COMPLETED,
            'saga_compensation_data' => null,
            'placed_at'       => now()->subDays($this->faker->numberBetween(0, 30)),
            'confirmed_at'    => in_array($status, [Order::STATUS_CONFIRMED, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED], true)
                ? now()->subDays($this->faker->numberBetween(0, 29))
                : null,
            'shipped_at'      => in_array($status, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED], true)
                ? now()->subDays($this->faker->numberBetween(0, 10))
                : null,
            'delivered_at'    => $status === Order::STATUS_DELIVERED
                ? now()->subDays($this->faker->numberBetween(0, 5))
                : null,
            'cancelled_at'    => $status === Order::STATUS_CANCELLED ? now() : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => Order::STATUS_PENDING,
            'saga_status'  => Order::SAGA_INVENTORY_RESERVED,
            'confirmed_at' => null,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => Order::STATUS_CONFIRMED,
            'saga_status'  => Order::SAGA_COMPLETED,
            'confirmed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => Order::STATUS_CANCELLED,
            'saga_status'  => Order::SAGA_COMPENSATED,
            'cancelled_at' => now(),
        ]);
    }
}
