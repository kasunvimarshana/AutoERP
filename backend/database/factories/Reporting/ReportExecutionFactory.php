<?php

namespace Database\Factories\Reporting;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Reporting\Enums\ReportExecutionStatus;
use App\Modules\Reporting\Models\Report;
use App\Modules\Reporting\Models\ReportExecution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Reporting\Models\ReportExecution>
 */
class ReportExecutionFactory extends Factory
{
    protected $model = ReportExecution::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'report_id' => Report::factory(),
            'executed_by_id' => User::factory(),
            'execution_time' => fake()->randomFloat(3, 0.1, 10),
            'parameters' => [],
            'result_count' => fake()->numberBetween(0, 1000),
            'status' => ReportExecutionStatus::COMPLETED->value,
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the execution is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportExecutionStatus::RUNNING->value,
            'execution_time' => null,
            'result_count' => 0,
        ]);
    }

    /**
     * Indicate that the execution failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportExecutionStatus::FAILED->value,
            'error_message' => fake()->sentence(),
            'result_count' => 0,
        ]);
    }
}
