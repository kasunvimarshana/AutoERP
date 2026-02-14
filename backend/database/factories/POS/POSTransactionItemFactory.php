<?php

namespace Database\Factories\POS;

use App\Modules\POS\Models\POSTransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class POSTransactionItemFactory extends Factory
{
    protected $model = POSTransactionItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $unitPrice = $this->faker->randomFloat(2, 5, 100);
        $discount = $this->faker->randomFloat(2, 0, $unitPrice * $quantity * 0.1);
        $taxRate = 10.00;
        $lineTotal = ($unitPrice * $quantity - $discount) * (1 + $taxRate / 100);

        return [
            'tenant_id' => 1,
            'pos_transaction_id' => 1,
            'sku_id' => 1,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax_rate' => $taxRate,
            'line_total' => $lineTotal,
        ];
    }
}
