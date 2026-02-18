<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Entities\Leave;

class LeaveFactory extends Factory
{
    protected $model = Leave::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-30 days', '+30 days');
        $days = $this->faker->numberBetween(1, 7);
        $endDate = (clone $startDate)->modify("+{$days} days");

        return [
            'tenant_id' => 1,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
        ];
    }
}
