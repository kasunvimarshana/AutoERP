<?php

namespace Database\Factories\POS;

use App\Modules\POS\Models\POSTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class POSTransactionFactory extends Factory
{
    protected $model = POSTransaction::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 10, 1000);
        $taxAmount = $subtotal * 0.1;
        $discountAmount = $this->faker->randomFloat(2, 0, $subtotal * 0.2);
        $totalAmount = $subtotal + $taxAmount - $discountAmount;
        $amountPaid = $this->faker->randomFloat(2, $totalAmount, $totalAmount + 50);
        $changeAmount = $amountPaid - $totalAmount;

        return [
            'tenant_id' => 1,
            'transaction_number' => 'POS-' . strtoupper($this->faker->unique()->bothify('##??####')),
            'customer_id' => null,
            'cashier_id' => 1,
            'transaction_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'digital']),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => $amountPaid,
            'change_amount' => $changeAmount,
            'status' => $this->faker->randomElement(['pending', 'completed', 'cancelled', 'refunded']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
        ]);
    }
}
