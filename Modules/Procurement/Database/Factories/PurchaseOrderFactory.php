<?php

declare(strict_types=1);

namespace Modules\Procurement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Entities\PurchaseOrder;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 50000);
        $taxAmount = $subtotal * 0.10;
        $discountAmount = fake()->boolean(30) ? fake()->randomFloat(2, 0, $subtotal * 0.10) : 0;
        $shippingAmount = fake()->randomFloat(2, 0, 500);
        $totalAmount = $subtotal + $taxAmount - $discountAmount + $shippingAmount;
        $orderDate = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'tenant_id' => 1,
            'order_number' => 'PO-'.date('Y').'-'.fake()->unique()->numerify('#####'),
            'purchase_requisition_id' => null,
            'supplier_id' => 1,
            'order_date' => $orderDate,
            'expected_delivery_date' => fake()->dateTimeBetween($orderDate, '+60 days'),
            'status' => fake()->randomElement(['draft', 'sent', 'confirmed', 'received', 'closed']),
            'payment_status' => fake()->randomElement(['unpaid', 'partial', 'paid']),
            'payment_terms' => fake()->randomElement(['Net 30', 'Net 60', 'COD']),
            'currency' => 'USD',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'shipping_amount' => $shippingAmount,
            'total_amount' => $totalAmount,
        ];
    }
}
