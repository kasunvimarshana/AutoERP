<?php

namespace Database\Factories\Reporting;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Reporting\Models\Dashboard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Reporting\Models\Dashboard>
 */
class DashboardFactory extends Factory
{
    protected $model = Dashboard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'layout_config' => [
                'columns' => 12,
                'rows' => 'auto',
            ],
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the dashboard is default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
