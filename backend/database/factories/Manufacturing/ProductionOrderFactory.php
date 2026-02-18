<?php

namespace Database\Factories\Manufacturing;

use App\Models\Tenant;
use App\Modules\Inventory\Models\Product;
use App\Modules\Manufacturing\Enums\ProductionOrderPriority;
use App\Modules\Manufacturing\Enums\ProductionOrderStatus;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use App\Modules\Manufacturing\Models\ProductionOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Manufacturing\Models\ProductionOrder>
 */
class ProductionOrderFactory extends Factory
{
    protected $model = ProductionOrder::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'production_order_number' => 'MO-'.fake()->unique()->numerify('######'),
            'product_id' => Product::factory(),
            'bill_of_material_id' => BillOfMaterial::factory(),
            'quantity' => fake()->randomFloat(4, 1, 1000),
            'warehouse_id' => null,
            'scheduled_start_date' => now(),
            'scheduled_end_date' => now()->addDays(fake()->numberBetween(1, 14)),
            'actual_start_date' => null,
            'actual_end_date' => null,
            'status' => ProductionOrderStatus::DRAFT->value,
            'priority' => ProductionOrderPriority::NORMAL->value,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'released_by' => null,
            'released_at' => null,
            'completed_by' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the production order is in draft status
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductionOrderStatus::DRAFT->value,
        ]);
    }

    /**
     * Indicate that the production order is released
     */
    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductionOrderStatus::RELEASED->value,
            'released_at' => now(),
        ]);
    }

    /**
     * Indicate that the production order is in progress
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductionOrderStatus::IN_PROGRESS->value,
            'actual_start_date' => now(),
            'released_at' => now()->subHours(2),
        ]);
    }

    /**
     * Indicate that the production order is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductionOrderStatus::COMPLETED->value,
            'actual_start_date' => now()->subDays(3),
            'actual_end_date' => now(),
            'released_at' => now()->subDays(4),
            'completed_at' => now(),
        ]);
    }

    /**
     * Set high priority
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => ProductionOrderPriority::HIGH->value,
        ]);
    }

    /**
     * Set urgent priority
     */
    public function urgentPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => ProductionOrderPriority::URGENT->value,
        ]);
    }

    /**
     * Set specific tenant
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Set specific product
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'tenant_id' => $product->tenant_id,
        ]);
    }
}
