<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity  = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 5, 200);

        return [
            'order_id'     => Order::factory(),
            'product_id'   => $this->faker->numberBetween(1, 1000),
            'product_name' => $this->faker->words(3, true),
            'product_sku'  => 'SKU-' . strtoupper($this->faker->bothify('??-####')),
            'quantity'     => $quantity,
            'unit_price'   => $unitPrice,
            'total_price'  => round($quantity * $unitPrice, 2),
            'status'       => $this->faker->randomElement(OrderItem::STATUSES),
        ];
    }
}
