<?php

namespace Database\Factories\Inventory;

use App\Enums\StockMovementType;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLedger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Inventory\Models\StockLedger>
 */
class StockLedgerFactory extends Factory
{
    protected $model = StockLedger::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $movementType = fake()->randomElement(StockMovementType::cases());
        $quantity = fake()->randomFloat(2, 1, 100);
        $unitCost = fake()->randomFloat(2, 10, 500);

        // Apply sign based on movement type
        if ($movementType->isDecrease()) {
            $quantity = -abs($quantity);
        } else {
            $quantity = abs($quantity);
        }

        return [
            'tenant_id' => Tenant::factory(),
            'product_id' => Product::factory(),
            'movement_type' => $movementType->value,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => abs($quantity) * $unitCost,
            'warehouse_id' => null,
            'location_id' => null,
            'batch_number' => null,
            'lot_number' => null,
            'serial_number' => null,
            'manufacturing_date' => null,
            'expiry_date' => null,
            'reference_type' => null,
            'reference_id' => null,
            'created_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
            'metadata' => [],
            'running_balance' => 0, // Will be calculated by model
            'transaction_date' => now(),
        ];
    }

    /**
     * Indicate that this is a purchase movement.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => StockMovementType::PURCHASE->value,
            'quantity' => abs($attributes['quantity']),
        ]);
    }

    /**
     * Indicate that this is a sale movement.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => StockMovementType::SALE->value,
            'quantity' => -abs($attributes['quantity']),
        ]);
    }

    /**
     * Indicate that this is an adjustment in movement.
     */
    public function adjustmentIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => StockMovementType::ADJUSTMENT_IN->value,
            'quantity' => abs($attributes['quantity']),
        ]);
    }

    /**
     * Indicate that this is an adjustment out movement.
     */
    public function adjustmentOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => StockMovementType::ADJUSTMENT_OUT->value,
            'quantity' => -abs($attributes['quantity']),
        ]);
    }

    /**
     * Add batch tracking information.
     */
    public function withBatch(?string $batchNumber = null): static
    {
        return $this->state(fn (array $attributes) => [
            'batch_number' => $batchNumber ?? 'BATCH-'.fake()->numerify('######'),
            'lot_number' => 'LOT-'.fake()->numerify('####'),
        ]);
    }

    /**
     * Add expiry tracking information.
     */
    public function withExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'manufacturing_date' => now()->subMonths(2),
            'expiry_date' => now()->addYear(),
        ]);
    }

    /**
     * Add serial number tracking.
     */
    public function withSerial(?string $serialNumber = null): static
    {
        return $this->state(fn (array $attributes) => [
            'serial_number' => $serialNumber ?? 'SN-'.fake()->unique()->numerify('##########'),
        ]);
    }

    /**
     * Set specific tenant for the stock ledger.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Set specific product for the stock ledger.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'tenant_id' => $product->tenant_id,
        ]);
    }
}
