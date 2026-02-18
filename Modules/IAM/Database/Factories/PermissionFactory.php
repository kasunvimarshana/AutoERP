<?php

declare(strict_types=1);

namespace Modules\IAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\IAM\Entities\Permission;

class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $modules = ['sales', 'inventory', 'accounting', 'hr', 'procurement', 'iam'];
        $resources = ['customer', 'order', 'product', 'invoice', 'employee', 'permission', 'role'];
        $actions = ['view', 'create', 'update', 'delete'];

        $module = $this->faker->randomElement($modules);
        $resource = $this->faker->randomElement($resources);
        $action = $this->faker->randomElement($actions);

        return [
            'name' => ucfirst($action).' '.ucfirst($resource),
            'slug' => $module.'.'.$resource.'.'.$action.'.'.$this->faker->unique()->numberBetween(1, 10000),
            'module' => $module,
            'resource' => $resource,
            'action' => $action,
            'description' => $this->faker->sentence(),
            'metadata' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the permission is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the permission is for a specific module.
     */
    public function forModule(string $module): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => $module,
        ]);
    }
}
