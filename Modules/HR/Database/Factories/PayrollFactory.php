<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Entities\Payroll;

class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        $month = $this->faker->numberBetween(1, 12);
        $year = $this->faker->numberBetween(2023, 2024);
        $basicSalary = $this->faker->randomFloat(2, 3000, 10000);
        $allowances = $this->faker->randomFloat(2, 0, 2000);
        $deductions = $this->faker->randomFloat(2, 0, 1000);
        $grossSalary = $basicSalary + $allowances;
        $netSalary = $grossSalary - $deductions;

        return [
            'tenant_id' => 1,
            'payroll_number' => 'PAY-'.$year.str_pad($month, 2, '0', STR_PAD_LEFT).'-'.$this->faker->unique()->numberBetween(100000, 999999),
            'month' => $month,
            'year' => $year,
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'gross_salary' => $grossSalary,
            'net_salary' => $netSalary,
            'status' => $this->faker->randomElement(['draft', 'processed', 'paid']),
        ];
    }
}
