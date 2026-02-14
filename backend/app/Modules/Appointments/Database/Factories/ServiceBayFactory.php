<?php

namespace App\Modules\Appointments\Database\Factories;

use App\Modules\Appointments\Models\ServiceBay;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceBayFactory extends Factory
{
    protected $model = ServiceBay::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'bay_number' => 'BAY-' . $this->faker->unique()->numberBetween(1, 999),
            'name' => 'Service Bay ' . $this->faker->unique()->numberBetween(1, 50),
            'capacity' => $this->faker->randomElement([1, 2]),
            'status' => 'available',
            'current_appointment_id' => null,
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
            'current_appointment_id' => null,
        ]);
    }

    public function occupied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'occupied',
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maintenance',
            'current_appointment_id' => null,
        ]);
    }
}
