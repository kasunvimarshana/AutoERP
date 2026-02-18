<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Entities\LeaveType;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'name' => $this->faker->randomElement(['Annual Leave', 'Sick Leave', 'Personal Leave', 'Maternity Leave']),
            'code' => 'LT-'.strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->sentence(),
            'max_days_per_year' => $this->faker->numberBetween(5, 30),
            'is_paid' => $this->faker->boolean(80),
            'requires_approval' => $this->faker->boolean(90),
            'is_carry_forward' => $this->faker->boolean(50),
            'max_carry_forward_days' => $this->faker->numberBetween(0, 10),
            'status' => 'active',
        ];
    }
}
