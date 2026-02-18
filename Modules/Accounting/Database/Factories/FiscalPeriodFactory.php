<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Entities\FiscalPeriod;

class FiscalPeriodFactory extends Factory
{
    protected $model = FiscalPeriod::class;

    public function definition(): array
    {
        $year = $this->faker->year();

        return [
            'tenant_id' => 1,
            'name' => 'FY '.$year,
            'period_type' => 'year',
            'fiscal_year' => $year,
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'status' => 'open',
        ];
    }
}
