<?php

namespace Database\Factories;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 5, 500);
        return [
            'name'        => $this->faker->words(3, true),
            'sku'         => strtoupper(Str::random(3)) . '-' . $this->faker->numerify('###'),
            'description' => $this->faker->sentence(),
            'price'       => $price,
            'cost_price'  => round($price * 0.5, 2),
            'category'    => $this->faker->randomElement(['electronics', 'office', 'stationery', 'clothing']),
            'unit'        => $this->faker->randomElement(['pcs', 'box', 'pack', 'kg']),
            'is_active'   => true,
            'metadata'    => null,
        ];
    }
}
