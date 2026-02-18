<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Entities\Invoice;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 1000, 50000);
        $taxAmount = $subtotal * 0.1;
        $totalAmount = $subtotal + $taxAmount;

        return [
            'tenant_id' => 1,
            'invoice_number' => 'INV-'.$this->faker->unique()->numerify('######'),
            'customer_id' => 1,
            'invoice_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'payment_terms' => 30,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
            'amount_paid' => 0,
            'amount_due' => $totalAmount,
            'status' => 'draft',
            'tags' => [],
        ];
    }
}
