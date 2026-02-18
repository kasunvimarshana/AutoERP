<?php

namespace Database\Factories\Reporting;

use App\Models\Tenant;
use App\Modules\Reporting\Enums\ExportFormat;
use App\Modules\Reporting\Models\Report;
use App\Modules\Reporting\Models\ScheduledReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Reporting\Models\ScheduledReport>
 */
class ScheduledReportFactory extends Factory
{
    protected $model = ScheduledReport::class;

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
            'schedule_cron' => '0 9 * * *', // Daily at 9 AM
            'recipients' => [
                fake()->email(),
                fake()->email(),
            ],
            'format' => fake()->randomElement([
                ExportFormat::CSV->value,
                ExportFormat::PDF->value,
                ExportFormat::EXCEL->value,
            ]),
            'is_active' => true,
            'last_run_at' => null,
            'next_run_at' => now()->addDay()->setTime(9, 0),
        ];
    }

    /**
     * Indicate that the schedule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the schedule is due.
     */
    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'next_run_at' => now()->subHour(),
        ]);
    }
}
