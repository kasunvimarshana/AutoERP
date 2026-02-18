<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Entities\OrganizationalUnit;

class OrganizationalUnitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = OrganizationalUnit::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1, // Will be set by test
            'organization_id' => null, // Must be set
            'location_id' => null,
            'parent_unit_id' => null,
            'code' => strtoupper($this->faker->unique()->bothify('UNIT-###')),
            'name' => [
                'en' => $this->faker->randomElement([
                    'Engineering',
                    'Sales',
                    'Marketing',
                    'Operations',
                    'Finance',
                    'HR',
                    'IT',
                ]).' '.$this->faker->randomElement(['Department', 'Division', 'Team']),
                'es' => $this->faker->randomElement([
                    'Ingeniería',
                    'Ventas',
                    'Marketing',
                    'Operaciones',
                    'Finanzas',
                    'RRHH',
                    'TI',
                ]).' '.$this->faker->randomElement(['Departamento', 'División', 'Equipo']),
            ],
            'description' => [
                'en' => $this->faker->optional()->sentence(),
            ],
            'unit_type' => $this->faker->randomElement([
                'division',
                'department',
                'team',
                'group',
                'project',
                'other',
            ]),
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'manager_id' => null,
            'email' => $this->faker->optional()->companyEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'settings' => [
                'budget' => $this->faker->optional()->numberBetween(10000, 1000000),
            ],
            'metadata' => [],
            'level' => 0,
            'path' => null, // Will be set by model
        ];
    }

    /**
     * Indicate that the unit is a department.
     */
    public function department(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_type' => 'department',
        ]);
    }

    /**
     * Indicate that the unit is a team.
     */
    public function team(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_type' => 'team',
        ]);
    }

    /**
     * Indicate that the unit is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}
