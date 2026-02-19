<?php

declare(strict_types=1);

namespace Modules\Appointment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Appointment\Models\Bay;
use Modules\Organization\Models\Branch;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Appointment\Models\Bay>
 */
class BayFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Bay::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'bay_number' => 'BAY-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'bay_type' => fake()->randomElement(['standard', 'express', 'diagnostic', 'detailing', 'heavy_duty']),
            'status' => fake()->randomElement(['available', 'occupied', 'maintenance', 'inactive']),
            'capacity' => fake()->numberBetween(1, 3),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
