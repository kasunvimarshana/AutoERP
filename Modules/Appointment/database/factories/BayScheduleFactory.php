<?php

declare(strict_types=1);

namespace Modules\Appointment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Bay;
use Modules\Appointment\Models\BaySchedule;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Appointment\Models\BaySchedule>
 */
class BayScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = BaySchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('+1 day', '+30 days');
        $endTime = (clone $startTime)->modify('+'.fake()->randomElement([30, 60, 90, 120, 180]).' minutes');

        return [
            'bay_id' => Bay::factory(),
            'appointment_id' => Appointment::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => fake()->randomElement(['scheduled', 'active', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
