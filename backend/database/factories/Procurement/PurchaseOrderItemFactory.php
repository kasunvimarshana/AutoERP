<?php

namespace Database\Factories\Procurement;

use App\Modules\Inventory\Models\Product;
use App\Modules\Procurement\Models\PurchaseOrder;
use App\Modules\Procurement\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Procurement\Models\PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->randomFloat(2, 10, 1000);
        $discountAmount = $quantity * $unitPrice * fake()->randomFloat(2, 0, 0.1);
        $taxAmount = ($quantity * $unitPrice - $discountAmount) * 0.1;
        $totalAmount = $quantity * $unitPrice - $discountAmount + $taxAmount;

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'received_quantity' => 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the item is partially received.
     */
    public function partiallyReceived(): static
    {
        return $this->state(function (array $attributes) {
            $receivedQty = $attributes['quantity'] * fake()->randomFloat(2, 0.1, 0.9);

            return [
                'received_quantity' => $receivedQty,
            ];
        });
    }

    /**
     * Indicate that the item is fully received.
     */
    public function fullyReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'received_quantity' => $attributes['quantity'],
        ]);
    }

    /**
     * Set specific purchase order for the item.
     */
    public function forPurchaseOrder(PurchaseOrder $purchaseOrder): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_order_id' => $purchaseOrder->id,
        ]);
    }

    /**
     * Set specific product for the item.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }
}
