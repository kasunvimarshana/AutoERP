<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->unique()->lexify('???-???-###')),
            'quantity' => $this->faker->numberBetween(10, 500),
            'reserved_quantity' => 0,
            'unit_price' => $this->faker->randomFloat(2, 1, 500),
            'status' => 'active',
        ];
    }
}
