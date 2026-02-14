<?php

namespace Database\Factories\Billing;

use App\Modules\Billing\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 100);
        $unitPrice = $this->faker->randomFloat(2, 10, 500);
        $discount = $this->faker->randomFloat(2, 0, $unitPrice * $quantity * 0.1);
        $taxRate = $this->faker->randomFloat(2, 0, 15);
        $subtotal = ($unitPrice * $quantity) - $discount;
        $taxAmount = $subtotal * ($taxRate / 100);
        $lineTotal = $subtotal + $taxAmount;

        return [
            'tenant_id' => 1,
            'invoice_id' => 1,
            'sku_id' => null,
            'description' => $this->faker->sentence(4),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax_rate' => $taxRate,
            'line_total' => $lineTotal,
        ];
    }
}
