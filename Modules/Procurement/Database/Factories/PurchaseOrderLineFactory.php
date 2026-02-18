<?php

declare(strict_types=1);

namespace Modules\Procurement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Entities\PurchaseOrderLine;

class PurchaseOrderLineFactory extends Factory
{
    protected $model = PurchaseOrderLine::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->randomFloat(2, 10, 1000);
        $taxRate = 10;
        $lineTotal = $quantity * $unitPrice;
        $taxAmount = $lineTotal * ($taxRate / 100);

        return [
            'tenant_id' => 1,
            'purchase_order_id' => 1,
            'product_id' => 1,
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_of_measure' => fake()->randomElement(['ea', 'kg', 'lb', 'm', 'ft']),
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
            'received_quantity' => 0,
        ];
    }
}
