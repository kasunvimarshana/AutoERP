<?php

namespace Database\Factories\Reporting;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Reporting\Enums\ReportType;
use App\Modules\Reporting\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Reporting\Models\Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'report_type' => fake()->randomElement([
                ReportType::TABLE->value,
                ReportType::CHART->value,
                ReportType::KPI->value,
                ReportType::EXPORT->value,
            ]),
            'module' => fake()->randomElement(['inventory', 'sales', 'procurement', 'manufacturing', 'finance']),
            'query_config' => [
                'pre_built' => true,
                'report_name' => 'stock_level',
            ],
            'schedule_config' => null,
            'is_public' => fake()->boolean(30),
            'created_by_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the report is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the report is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
