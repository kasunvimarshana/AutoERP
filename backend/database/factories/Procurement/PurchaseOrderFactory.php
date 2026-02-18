<?php

namespace Database\Factories\Procurement;

use App\Models\Tenant;
use App\Modules\Procurement\Enums\PurchaseOrderStatus;
use App\Modules\Procurement\Models\PurchaseOrder;
use App\Modules\Procurement\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Procurement\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);
        $discountAmount = $subtotal * fake()->randomFloat(2, 0, 0.1);
        $taxAmount = ($subtotal - $discountAmount) * 0.1;
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        return [
            'tenant_id' => Tenant::factory(),
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => null,
            'po_number' => 'PO-'.fake()->unique()->numerify('######'),
            'po_date' => now(),
            'expected_delivery_date' => now()->addDays(fake()->numberBetween(7, 30)),
            'status' => PurchaseOrderStatus::PENDING->value,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'notes' => fake()->optional()->sentence(),
            'approved_by' => null,
            'approved_at' => null,
            'received_by' => null,
            'received_at' => null,
        ];
    }

    /**
     * Indicate that the purchase order is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::DRAFT->value,
        ]);
    }

    /**
     * Indicate that the purchase order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::PENDING->value,
        ]);
    }

    /**
     * Indicate that the purchase order is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::APPROVED->value,
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the purchase order is received.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::RECEIVED->value,
            'received_at' => now(),
        ]);
    }

    /**
     * Set specific tenant for the purchase order.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Set specific supplier for the purchase order.
     */
    public function forSupplier(Supplier $supplier): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_id' => $supplier->id,
            'tenant_id' => $supplier->tenant_id,
        ]);
    }
}
