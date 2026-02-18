<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Entities\Attendance;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-30 days', 'now');
        $checkIn = (clone $date)->setTime(8, $this->faker->numberBetween(0, 30), 0);
        $checkOut = (clone $checkIn)->modify('+'.$this->faker->numberBetween(7, 10).' hours');

        return [
            'tenant_id' => 1,
            'date' => $date->format('Y-m-d'),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'work_hours' => $checkIn->diff($checkOut)->h + ($checkIn->diff($checkOut)->i / 60),
            'status' => $this->faker->randomElement(['present', 'late']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
