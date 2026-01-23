<?php

declare(strict_types=1);

namespace Modules\JobCard\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\JobCard\Models\JobCard;
use Modules\Organization\Models\Branch;

/**
 * JobCard Factory
 *
 * @extends Factory<JobCard>
 */
class JobCardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = JobCard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_number' => JobCard::generateJobNumber(),
            'vehicle_id' => Vehicle::factory(),
            'customer_id' => Customer::factory(),
            'branch_id' => Branch::factory(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'estimated_hours' => $this->faker->randomFloat(2, 1, 20),
            'actual_hours' => $this->faker->optional()->randomFloat(2, 1, 20),
            'parts_total' => $this->faker->randomFloat(2, 0, 1000),
            'labor_total' => $this->faker->randomFloat(2, 0, 500),
            'grand_total' => $this->faker->randomFloat(2, 0, 1500),
            'notes' => $this->faker->optional()->sentence(),
            'customer_complaints' => $this->faker->optional()->paragraph(),
            'started_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'completed_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the job card is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the job card is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'started_at' => now()->subHours(2),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the job card is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subDays(1),
            'completed_at' => now(),
        ]);
    }
}
