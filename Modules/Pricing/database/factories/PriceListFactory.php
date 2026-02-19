<?php

declare(strict_types=1);

namespace Modules\Pricing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pricing\Models\PriceList;

class PriceListFactory extends Factory
{
    protected $model = PriceList::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => strtoupper($this->faker->unique()->lexify('PL???')),
            'description' => $this->faker->sentence(),
            'status' => 'active',
            'currency_code' => 'USD',
            'is_default' => false,
            'priority' => $this->faker->numberBetween(0, 10),
        ];
    }
}
