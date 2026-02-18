<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Entities\SalesOrderLine;

/**
 * Sales Order Line Factory
 *
 * Generates order lines with quantities, prices, discounts, and taxes.
 */
class SalesOrderLineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = SalesOrderLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->randomFloat(2, 10, 5000);
        $discountPercent = fake()->boolean(30) ? fake()->randomFloat(2, 0, 25) : 0;
        $taxPercent = fake()->randomFloat(2, 0, 20);

        $subtotal = $quantity * $unitPrice;
        $discountAmount = $subtotal * ($discountPercent / 100);
        $amountAfterDiscount = $subtotal - $discountAmount;
        $taxAmount = $amountAfterDiscount * ($taxPercent / 100);
        $lineTotal = $amountAfterDiscount + $taxAmount;

        return [
            'tenant_id' => 1,
            'sales_order_id' => null,
            'product_id' => null,
            'description' => fake()->sentence(6),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ];
    }

    /**
     * Indicate that the line has no discount.
     */
    public function noDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'];
            $unitPrice = $attributes['unit_price'];
            $taxPercent = $attributes['tax_percent'];

            $subtotal = $quantity * $unitPrice;
            $taxAmount = $subtotal * ($taxPercent / 100);
            $lineTotal = $subtotal + $taxAmount;

            return [
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
            ];
        });
    }

    /**
     * Indicate that the line has no tax.
     */
    public function noTax(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'];
            $unitPrice = $attributes['unit_price'];
            $discountAmount = $attributes['discount_amount'];

            $subtotal = $quantity * $unitPrice;
            $lineTotal = $subtotal - $discountAmount;

            return [
                'tax_percent' => 0,
                'tax_amount' => 0,
                'line_total' => $lineTotal,
            ];
        });
    }

    /**
     * Indicate that the line is for a service.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement([
                'Consulting Services - '.fake()->numberBetween(1, 40).' hours',
                'Professional Services - Project '.fake()->word(),
                'Maintenance Service - Monthly',
                'Support Services - Annual',
                'Training Session - '.fake()->numberBetween(1, 5).' days',
            ]),
            'quantity' => fake()->randomFloat(2, 1, 200),
            'unit_price' => fake()->randomFloat(2, 50, 500),
        ]);
    }

    /**
     * Indicate that the line has a high quantity.
     */
    public function bulk(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->randomFloat(2, 100, 10000);
            $unitPrice = fake()->randomFloat(2, 1, 100);
            $discountPercent = fake()->randomFloat(2, 10, 30);
            $taxPercent = $attributes['tax_percent'];

            $subtotal = $quantity * $unitPrice;
            $discountAmount = $subtotal * ($discountPercent / 100);
            $amountAfterDiscount = $subtotal - $discountAmount;
            $taxAmount = $amountAfterDiscount * ($taxPercent / 100);
            $lineTotal = $amountAfterDiscount + $taxAmount;

            return [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
            ];
        });
    }

    /**
     * Indicate that the line has a high unit price.
     */
    public function premium(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->randomFloat(2, 1, 10);
            $unitPrice = fake()->randomFloat(2, 5000, 50000);
            $discountPercent = $attributes['discount_percent'];
            $taxPercent = $attributes['tax_percent'];

            $subtotal = $quantity * $unitPrice;
            $discountAmount = $subtotal * ($discountPercent / 100);
            $amountAfterDiscount = $subtotal - $discountAmount;
            $taxAmount = $amountAfterDiscount * ($taxPercent / 100);
            $lineTotal = $amountAfterDiscount + $taxAmount;

            return [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
            ];
        });
    }
}
