<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Entities\PerformanceReview;

class PerformanceReviewFactory extends Factory
{
    protected $model = PerformanceReview::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '-6 months');
        $endDate = (clone $startDate)->modify('+6 months');

        return [
            'tenant_id' => 1,
            'review_period_start' => $startDate,
            'review_period_end' => $endDate,
            'overall_rating' => $this->faker->numberBetween(1, 5),
            'strengths' => $this->faker->paragraph(),
            'areas_for_improvement' => $this->faker->paragraph(),
            'goals' => $this->faker->paragraph(),
            'achievements' => $this->faker->paragraph(),
            'comments' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['draft', 'completed']),
        ];
    }
}
