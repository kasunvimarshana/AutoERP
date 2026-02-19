<?php

declare(strict_types=1);

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\Product;

/**
 * Product Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Product\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'PRD-'.$this->faker->unique()->numerify('########'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'barcode' => $this->faker->optional()->ean13(),
            'type' => $this->faker->randomElement(['goods', 'services', 'digital']),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'cost_price' => $this->faker->randomFloat(2, 10, 1000),
            'selling_price' => $this->faker->randomFloat(2, 20, 1500),
            'track_inventory' => $this->faker->boolean(80),
            'current_stock' => $this->faker->numberBetween(0, 500),
            'reorder_level' => $this->faker->numberBetween(10, 50),
            'reorder_quantity' => $this->faker->numberBetween(20, 100),
            'min_stock_level' => $this->faker->numberBetween(5, 20),
            'manufacturer' => $this->faker->optional()->company(),
            'brand' => $this->faker->optional()->word(),
            'is_taxable' => $this->faker->boolean(90),
            'allow_discount' => $this->faker->boolean(70),
            'is_featured' => $this->faker->boolean(20),
        ];
    }
}
