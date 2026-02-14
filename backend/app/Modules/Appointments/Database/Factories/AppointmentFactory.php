<?php

namespace App\Modules\Appointments\Database\Factories;

use App\Modules\Appointments\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'customer_id' => 1,
            'vehicle_id' => 1,
            'service_bay_id' => null,
            'appointment_number' => 'APT-' . strtoupper($this->faker->unique()->bothify('##??####')),
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'scheduled_time' => $this->faker->time('H:i:s'),
            'duration_minutes' => $this->faker->randomElement([30, 60, 90, 120]),
            'service_type' => $this->faker->randomElement(['Oil Change', 'Tire Rotation', 'Brake Service', 'General Inspection', 'Maintenance']),
            'status' => $this->faker->randomElement(['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled']),
            'notes' => $this->faker->optional()->sentence(),
            'confirmed_at' => null,
            'completed_at' => null,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'confirmed_at' => now()->subHour(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'confirmed_at' => now()->subHours(3),
            'completed_at' => now(),
        ]);
    }
}
