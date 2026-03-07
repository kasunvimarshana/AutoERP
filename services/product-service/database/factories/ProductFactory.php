<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name'           => $this->faker->unique()->words(3, true),
            'description'    => $this->faker->optional()->paragraph(),
            'sku'            => strtoupper($this->faker->unique()->bothify('??##-???-###')),
            'price'          => $this->faker->randomFloat(4, 0.99, 999.99),
            'category'       => $this->faker->randomElement([
                'Electronics', 'Furniture', 'Kitchen', 'Sports', 'Clothing', 'Books',
            ]),
            'status'         => $this->faker->randomElement(['active', 'inactive']),
            'stock_quantity' => $this->faker->numberBetween(0, 500),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_quantity' => 0]);
    }

    public function inStock(): static
    {
        return $this->state(['stock_quantity' => $this->faker->numberBetween(1, 500)]);
    }
}
