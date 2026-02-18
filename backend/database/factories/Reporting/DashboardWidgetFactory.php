<?php

namespace Database\Factories\Reporting;

use App\Models\Tenant;
use App\Modules\Reporting\Enums\WidgetType;
use App\Modules\Reporting\Models\Dashboard;
use App\Modules\Reporting\Models\DashboardWidget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Reporting\Models\DashboardWidget>
 */
class DashboardWidgetFactory extends Factory
{
    protected $model = DashboardWidget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'dashboard_id' => Dashboard::factory(),
            'widget_type' => fake()->randomElement([
                WidgetType::KPI->value,
                WidgetType::CHART->value,
                WidgetType::TABLE->value,
                WidgetType::RECENT_ACTIVITY->value,
                WidgetType::QUICK_STATS->value,
            ]),
            'title' => fake()->words(3, true),
            'config' => [
                'report_id' => null,
                'refresh_interval' => 60,
            ],
            'position_x' => fake()->numberBetween(0, 8),
            'position_y' => fake()->numberBetween(0, 10),
            'width' => fake()->numberBetween(3, 6),
            'height' => fake()->numberBetween(2, 4),
        ];
    }
}
