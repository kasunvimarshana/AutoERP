<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'sku' => 'SKU-' . fake()->unique()->numerify('######'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'cost_price' => fake()->randomFloat(2, 5, 500),
            'unit_of_measure' => fake()->randomElement(['pcs', 'kg', 'liter', 'box', 'pack']),
            'track_inventory' => fake()->boolean(80),
            'track_batch' => fake()->boolean(30),
            'track_serial' => fake()->boolean(20),
            'track_expiry' => fake()->boolean(40),
            'min_stock_level' => fake()->randomFloat(2, 5, 50),
            'max_stock_level' => fake()->randomFloat(2, 100, 500),
            'reorder_point' => fake()->randomFloat(2, 10, 100),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function trackInventory(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_inventory' => true,
        ]);
    }

    public function noTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_inventory' => false,
            'track_batch' => false,
            'track_serial' => false,
            'track_expiry' => false,
        ]);
    }
}
