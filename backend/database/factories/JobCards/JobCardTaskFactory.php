<?php

namespace Database\Factories\JobCards;

use App\Modules\JobCards\Models\JobCardTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobCardTaskFactory extends Factory
{
    protected $model = JobCardTask::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $unitPrice = $this->faker->randomFloat(2, 10, 500);
        $discount = $this->faker->randomFloat(2, 0, $unitPrice * $quantity * 0.2);
        $taxRate = $this->faker->randomElement([0, 5, 10, 15]);
        $subtotal = ($unitPrice * $quantity) - $discount;
        $lineTotal = $subtotal + ($subtotal * $taxRate / 100);

        return [
            'tenant_id' => 1,
            'job_card_id' => 1,
            'sku_id' => null,
            'task_type' => $this->faker->randomElement(['service', 'part']),
            'description' => $this->faker->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax_rate' => $taxRate,
            'line_total' => $lineTotal,
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'completed_by' => null,
            'completed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'completed_by' => null,
            'completed_at' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'completed_by' => null,
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_by' => 1,
            'completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => 'service',
        ]);
    }

    public function part(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_type' => 'part',
        ]);
    }
}
