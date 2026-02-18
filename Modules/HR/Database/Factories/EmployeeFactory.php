<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Entities\Employee;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'employee_number' => 'EMP-'.date('Y').'-'.str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->phoneNumber(),
            'date_of_birth' => $this->faker->date('Y-m-d', '-25 years'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'hire_date' => $this->faker->date('Y-m-d', '-2 years'),
            'employment_type' => $this->faker->randomElement(['full-time', 'part-time', 'contract', 'intern']),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'salary' => $this->faker->randomFloat(2, 30000, 150000),
        ];
    }
}
