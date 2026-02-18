<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Entities\SalesOrder;

/**
 * Sales Order Factory
 *
 * Generates orders with status, totals, and dates.
 */
class SalesOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = SalesOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 50000);
        $taxPercent = fake()->randomFloat(2, 0, 0.20);
        $taxAmount = $subtotal * $taxPercent;
        $discountAmount = fake()->boolean(30) ? fake()->randomFloat(2, 0, $subtotal * 0.15) : 0;
        $shippingAmount = fake()->randomFloat(2, 0, 500);
        $totalAmount = $subtotal + $taxAmount - $discountAmount + $shippingAmount;

        $orderDate = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'tenant_id' => 1,
            'order_number' => 'SO-'.fake()->unique()->numerify('######'),
            'customer_id' => null,
            'order_date' => $orderDate,
            'delivery_date' => fake()->dateTimeBetween($orderDate, '+30 days'),
            'status' => fake()->randomElement(['draft', 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']),
            'payment_status' => fake()->randomElement(['pending', 'partial', 'paid', 'refunded']),
            'payment_method' => fake()->randomElement(['credit_card', 'bank_transfer', 'paypal', 'check', 'cash', 'other']),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'CAD', 'AUD']),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'shipping_amount' => $shippingAmount,
            'total_amount' => $totalAmount,
            'notes' => fake()->boolean(40) ? fake()->paragraph() : null,
            'internal_notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'terms_and_conditions' => fake()->boolean(50) ? fake()->paragraph(2) : null,
        ];
    }

    /**
     * Indicate that the order is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'payment_status' => fake()->randomElement(['pending', 'partial', 'paid']),
        ]);
    }

    /**
     * Indicate that the order is shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'payment_status' => fake()->randomElement(['partial', 'paid']),
        ]);
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'payment_status' => fake()->randomElement(['pending', 'refunded']),
        ]);
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate that the order has no discount.
     */
    public function noDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $taxAmount = $attributes['tax_amount'];
            $shippingAmount = $attributes['shipping_amount'];

            return [
                'discount_amount' => 0,
                'total_amount' => $subtotal + $taxAmount + $shippingAmount,
            ];
        });
    }

    /**
     * Indicate that the order has no shipping cost.
     */
    public function noShipping(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $taxAmount = $attributes['tax_amount'];
            $discountAmount = $attributes['discount_amount'];

            return [
                'shipping_amount' => 0,
                'total_amount' => $subtotal + $taxAmount - $discountAmount,
            ];
        });
    }

    /**
     * Indicate that the order is large (high value).
     */
    public function large(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->randomFloat(2, 50000, 500000);
            $taxPercent = 0.10;
            $taxAmount = $subtotal * $taxPercent;
            $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.10);
            $shippingAmount = fake()->randomFloat(2, 100, 1000);

            return [
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $subtotal + $taxAmount - $discountAmount + $shippingAmount,
            ];
        });
    }
}
