<?php

declare(strict_types=1);

namespace Modules\Appointment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Appointment\Models\Appointment;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Organization\Models\Branch;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Appointment\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scheduledDateTime = fake()->dateTimeBetween('+1 day', '+30 days');

        return [
            'appointment_number' => 'APT-'.fake()->unique()->numerify('########'),
            'customer_id' => Customer::factory(),
            'vehicle_id' => Vehicle::factory(),
            'branch_id' => Branch::factory(),
            'service_type' => fake()->randomElement([
                'oil_change',
                'tire_rotation',
                'brake_service',
                'engine_diagnostic',
                'general_inspection',
                'transmission',
                'electrical',
                'detailing',
                'other',
            ]),
            'scheduled_date_time' => $scheduledDateTime,
            'duration' => fake()->randomElement([30, 60, 90, 120, 180, 240]),
            'status' => fake()->randomElement(['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show']),
            'notes' => fake()->optional()->sentence(),
            'customer_notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the appointment is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
        ]);
    }

    /**
     * Indicate that the appointment is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the appointment is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'confirmed_at' => now()->subHour(),
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the appointment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'confirmed_at' => now()->subHours(3),
            'started_at' => now()->subHours(2),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the appointment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
