<?php

namespace Database\Factories\Manufacturing;

use App\Modules\Inventory\Models\Product;
use App\Modules\Manufacturing\Models\ProductionOrder;
use App\Modules\Manufacturing\Models\ProductionOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Manufacturing\Models\ProductionOrderItem>
 */
class ProductionOrderItemFactory extends Factory
{
    protected $model = ProductionOrderItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $plannedQuantity = fake()->randomFloat(4, 1, 100);

        return [
            'production_order_id' => ProductionOrder::factory(),
            'product_id' => Product::factory(),
            'planned_quantity' => $plannedQuantity,
            'consumed_quantity' => 0,
            'uom_id' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the item is fully consumed
     */
    public function consumed(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumed_quantity' => $attributes['planned_quantity'],
        ]);
    }

    /**
     * Indicate that the item is partially consumed
     */
    public function partiallyConsumed(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumed_quantity' => $attributes['planned_quantity'] * 0.5,
        ]);
    }

    /**
     * Set specific production order
     */
    public function forProductionOrder(ProductionOrder $productionOrder): static
    {
        return $this->state(fn (array $attributes) => [
            'production_order_id' => $productionOrder->id,
        ]);
    }

    /**
     * Set specific product
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }
}
