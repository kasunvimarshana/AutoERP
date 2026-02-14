<?php

namespace Database\Factories\Billing;

use App\Modules\Billing\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 10000);
        $taxAmount = $subtotal * 0.1;
        $discountAmount = $this->faker->randomFloat(2, 0, $subtotal * 0.1);
        $totalAmount = $subtotal + $taxAmount - $discountAmount;
        $paidAmount = $this->faker->randomFloat(2, 0, $totalAmount);
        $balance = $totalAmount - $paidAmount;

        $invoiceDate = $this->faker->dateTimeBetween('-3 months', 'now');
        $dueDate = $this->faker->dateTimeBetween($invoiceDate, '+30 days');

        return [
            'tenant_id' => 1,
            'customer_id' => null,
            'invoice_number' => 'INV-' . strtoupper($this->faker->unique()->bothify('####??####')),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'status' => $this->faker->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'paid_amount' => 0,
            'balance' => $attributes['total_amount'],
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_amount' => $attributes['total_amount'],
            'balance' => 0,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
