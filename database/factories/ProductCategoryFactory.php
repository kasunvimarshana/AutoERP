<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\ProductCategory;
use Modules\Tenant\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Product\Models\ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random tenant, or create one if none exist
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();

        $name = fake()->words(2, true);

        return [
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'name' => ucfirst($name),
            'code' => 'CAT-'.fake()->unique()->numerify('####'),
            'description' => fake()->sentence(),
            'metadata' => [
                'icon' => fake()->randomElement(['📦', '🛍️', '🏷️', '📊', '🔧']),
                'color' => fake()->hexColor(),
            ],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific tenant for the category.
     */
    public function forTenant(Tenant|string $tenant): static
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Set a parent category.
     */
    public function withParent(ProductCategory|string $parent): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            if ($parent instanceof ProductCategory) {
                return [
                    'parent_id' => $parent->id,
                    'tenant_id' => $parent->tenant_id,
                ];
            }

            $parentCategory = ProductCategory::findOrFail($parent);

            return [
                'parent_id' => $parentCategory->id,
                'tenant_id' => $parentCategory->tenant_id,
            ];
        });
    }
}
