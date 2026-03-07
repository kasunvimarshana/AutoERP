<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'product_sku' => strtoupper($this->faker->unique()->lexify('???-###')),
            'product_name' => $this->faker->words(3, true),
            'quantity' => $this->faker->numberBetween(1, 20),
            'unit_price' => $this->faker->randomFloat(2, 10, 500),
            'total_price' => fn (array $attrs) => $attrs['quantity'] * $attrs['unit_price'],
            'status' => 'pending',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
