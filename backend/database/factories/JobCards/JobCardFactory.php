<?php

namespace Database\Factories\JobCards;

use App\Modules\JobCards\Models\JobCard;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobCardFactory extends Factory
{
    protected $model = JobCard::class;

    public function definition(): array
    {
        $estimatedCost = $this->faker->randomFloat(2, 100, 5000);
        $actualCost = $this->faker->randomFloat(2, $estimatedCost * 0.8, $estimatedCost * 1.2);
        $openedDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $closedDate = $this->faker->boolean(60) ? $this->faker->dateTimeBetween($openedDate, 'now') : null;

        return [
            'tenant_id' => 1,
            'appointment_id' => null,
            'customer_id' => 1,
            'vehicle_id' => 1,
            'job_card_number' => 'JC-' . strtoupper($this->faker->unique()->bothify('##??####')),
            'opened_date' => $openedDate,
            'closed_date' => $closedDate,
            'status' => $this->faker->randomElement(['open', 'in_progress', 'completed', 'cancelled']),
            'technician_id' => 1,
            'estimated_cost' => $estimatedCost,
            'actual_cost' => $actualCost,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'closed_date' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'closed_date' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'closed_date' => $this->faker->dateTimeBetween($attributes['opened_date'], 'now'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'closed_date' => $this->faker->dateTimeBetween($attributes['opened_date'], 'now'),
        ]);
    }
}
