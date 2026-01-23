<?php

declare(strict_types=1);

namespace Modules\Invoice\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Invoice\Models\Invoice;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Invoice\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $taxRate = $this->faker->randomFloat(2, 0, 20);
        $taxAmount = $subtotal * ($taxRate / 100);
        $discountAmount = $this->faker->randomFloat(2, 0, 100);
        $totalAmount = $subtotal + $taxAmount - $discountAmount;
        $amountPaid = $this->faker->randomFloat(2, 0, $totalAmount);
        $balance = $totalAmount - $amountPaid;

        return [
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => $amountPaid,
            'balance' => $balance,
            'status' => $this->faker->randomElement(['draft', 'pending', 'sent', 'partial', 'paid']),
            'payment_terms' => $this->faker->randomElement(['Net 30', 'Net 60', 'Due on Receipt']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
