<?php

namespace Database\Factories\Manufacturing;

use App\Models\Tenant;
use App\Modules\Manufacturing\Enums\WorkOrderStatus;
use App\Modules\Manufacturing\Models\ProductionOrder;
use App\Modules\Manufacturing\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Manufacturing\Models\WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'work_order_number' => 'WO-'.fake()->unique()->numerify('######'),
            'production_order_id' => ProductionOrder::factory(),
            'workstation' => fake()->randomElement(['Assembly Line 1', 'Assembly Line 2', 'Packaging', 'Quality Control']),
            'description' => fake()->optional()->sentence(),
            'scheduled_start' => now(),
            'scheduled_end' => now()->addHours(fake()->numberBetween(2, 24)),
            'actual_start' => null,
            'actual_end' => null,
            'status' => WorkOrderStatus::PENDING->value,
            'notes' => fake()->optional()->sentence(),
            'assigned_to' => null,
            'started_by' => null,
            'completed_by' => null,
        ];
    }

    /**
     * Indicate that the work order is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkOrderStatus::PENDING->value,
        ]);
    }

    /**
     * Indicate that the work order is in progress
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkOrderStatus::IN_PROGRESS->value,
            'actual_start' => now(),
        ]);
    }

    /**
     * Indicate that the work order is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkOrderStatus::COMPLETED->value,
            'actual_start' => now()->subHours(4),
            'actual_end' => now(),
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
     * Set specific production order
     */
    public function forProductionOrder(ProductionOrder $productionOrder): static
    {
        return $this->state(fn (array $attributes) => [
            'production_order_id' => $productionOrder->id,
            'tenant_id' => $productionOrder->tenant_id,
        ]);
    }
}
