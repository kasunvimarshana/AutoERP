<?php

namespace Database\Factories;

use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 20, 1000);
        $tax      = round($subtotal * 0.1, 2);
        return [
            'order_number'   => 'ORD-' . strtoupper(Str::random(10)),
            'customer_name'  => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'status'         => $this->faker->randomElement(['pending', 'confirmed', 'processing']),
            'subtotal'       => $subtotal,
            'tax'            => $tax,
            'total'          => $subtotal + $tax,
            'notes'          => null,
            'metadata'       => null,
            'saga_state'     => null,
        ];
    }
}
