<?php

namespace Database\Factories\Finance;

use App\Models\Tenant;
use App\Modules\Finance\Models\CostCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Finance\Models\CostCenter>
 */
class CostCenterFactory extends Factory
{
    protected $model = CostCenter::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'code' => 'CC-'.fake()->unique()->numerify('####'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
