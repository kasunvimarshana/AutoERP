<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        $quantity       = $this->faker->numberBetween(0, 500);
        $reorderLevel   = $this->faker->numberBetween(5, 50);
        $reserved       = $quantity > 0 ? $this->faker->numberBetween(0, (int) ($quantity * 0.2)) : 0;

        return [
            'product_id'         => $this->faker->numberBetween(1, 10000),
            'quantity'           => $quantity,
            'reserved_quantity'  => $reserved,
            'warehouse_location' => strtoupper($this->faker->randomLetter())
                                    . '-'
                                    . $this->faker->numberBetween(1, 10)
                                    . '-'
                                    . str_pad((string) $this->faker->numberBetween(1, 20), 2, '0', STR_PAD_LEFT),
            'reorder_level'      => $reorderLevel,
            'reorder_quantity'   => $this->faker->numberBetween(10, 200),
            'unit_cost'          => $this->faker->randomFloat(4, 1, 500),
            'status'             => $this->faker->randomElement(['active', 'inactive']),
            'last_counted_at'    => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
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

    public function inStock(int $quantity = 100): static
    {
        return $this->state(['quantity' => $quantity]);
    }

    public function outOfStock(): static
    {
        return $this->state(['quantity' => 0, 'reserved_quantity' => 0]);
    }

    public function lowStock(): static
    {
        return $this->state(function (array $attributes): array {
            $reorderLevel = $attributes['reorder_level'] ?? 10;
            return [
                'quantity'         => $this->faker->numberBetween(1, $reorderLevel),
                'reorder_level'    => $reorderLevel,
            ];
        });
    }
}
