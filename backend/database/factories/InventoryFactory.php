<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'warehouse_location' => 'WH-' . $this->faker->bothify('??-##'),
            'quantity'           => $this->faker->numberBetween(0, 500),
            'reserved_quantity'  => 0,
            'reorder_point'      => $this->faker->numberBetween(5, 25),
            'reorder_quantity'   => $this->faker->numberBetween(50, 200),
            'last_restocked_at'  => now()->subDays($this->faker->numberBetween(1, 60)),
        ];
    }
}
