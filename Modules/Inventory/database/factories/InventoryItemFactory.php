<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Organization\Models\Branch;

/**
 * Inventory Item Factory
 *
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = InventoryItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Parts', 'Oil', 'Filters', 'Tires', 'Batteries', 'Accessories'];
        $unitCost = $this->faker->randomFloat(2, 5, 500);

        return [
            'branch_id' => Branch::factory(),
            'item_code' => 'ITEM'.str_pad((string) $this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'item_name' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement($categories),
            'description' => $this->faker->optional()->sentence(),
            'unit_of_measure' => $this->faker->randomElement(['EA', 'L', 'KG', 'SET']),
            'reorder_level' => $this->faker->numberBetween(5, 20),
            'reorder_quantity' => $this->faker->numberBetween(10, 50),
            'unit_cost' => $unitCost,
            'selling_price' => $unitCost * $this->faker->randomFloat(2, 1.2, 2.5),
            'stock_on_hand' => $this->faker->numberBetween(0, 100),
            'is_dummy_item' => false,
        ];
    }

    /**
     * Indicate that the item is a dummy item.
     */
    public function dummyItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dummy_item' => true,
            'stock_on_hand' => 0,
        ]);
    }

    /**
     * Indicate that the item has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_on_hand' => $this->faker->numberBetween(0, $attributes['reorder_level'] - 1),
        ]);
    }
}
