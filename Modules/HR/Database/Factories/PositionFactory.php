<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Entities\Position;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'title' => $this->faker->jobTitle(),
            'code' => 'POS-'.strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->sentence(),
            'grade' => $this->faker->randomElement(['Junior', 'Mid', 'Senior', 'Lead', 'Manager']),
            'min_salary' => $minSalary = $this->faker->numberBetween(30000, 80000),
            'max_salary' => $minSalary + $this->faker->numberBetween(10000, 50000),
            'responsibilities' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
