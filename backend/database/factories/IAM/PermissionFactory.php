<?php

namespace Database\Factories\IAM;

use App\Modules\IAM\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\IAM\Models\Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->randomElement(['create', 'read', 'update', 'delete']);
        $resource = fake()->randomElement(['products', 'customers', 'orders', 'invoices', 'users']);
        $name = ucfirst($action).' '.ucfirst($resource);
        $slug = Str::slug($name).'-'.Str::random(8); // Add random suffix to ensure uniqueness

        return [
            'name' => $name.' '.Str::random(4), // Add random suffix to name as well
            'slug' => $slug,
            'module' => fake()->randomElement(['inventory', 'crm', 'pos', 'procurement', 'iam']),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Create permission for specific module.
     */
    public function forModule(string $module): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => $module,
        ]);
    }

    /**
     * Create a view permission.
     */
    public function viewPermission(string $resource): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "View {$resource}",
            'slug' => "view-{$resource}",
        ]);
    }

    /**
     * Create a create permission.
     */
    public function createPermission(string $resource): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "Create {$resource}",
            'slug' => "create-{$resource}",
        ]);
    }

    /**
     * Create an update permission.
     */
    public function updatePermission(string $resource): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "Update {$resource}",
            'slug' => "update-{$resource}",
        ]);
    }

    /**
     * Create a delete permission.
     */
    public function deletePermission(string $resource): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "Delete {$resource}",
            'slug' => "delete-{$resource}",
        ]);
    }
}
