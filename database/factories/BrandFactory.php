<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'logo' => fake()->optional()->imageUrl(200, 200, 'business', true),
        ];
    }
}
