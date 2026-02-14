<?php

namespace Database\Factories;

use App\Modules\Billing\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);
        $taxAmount = $subtotal * 0.1;
        $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.15);
        $totalAmount = $subtotal + $taxAmount - $discountAmount;
        
        return [
            'uuid' => Str::uuid(),
            'invoice_number' => 'INV-' . fake()->unique()->numerify('######'),
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
        ]);
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
                'paid_amount' => $attributes['total_amount'],
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => now()->subDays(10),
        ]);
    }

    public function partiallyPaid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'partial',
                'paid_amount' => $attributes['total_amount'] * 0.5,
            ];
        });
    }
}
