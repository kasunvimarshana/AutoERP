<?php

namespace Database\Factories\Finance;

use App\Models\Tenant;
use App\Modules\Finance\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Finance\Models\FiscalYear>
 */
class FiscalYearFactory extends Factory
{
    protected $model = FiscalYear::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now');
        $endDate = (clone $startDate)->modify('+1 year');

        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'FY '.$startDate->format('Y'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_closed' => true,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
