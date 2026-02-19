<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Enums\ProductType;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;
use Modules\Product\Models\Unit;
use Modules\Tenant\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Product\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random tenant, or create one if none exist
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();

        // Get random units for the tenant
        $units = Unit::where('tenant_id', $tenant->id)->inRandomOrder()->limit(2)->pluck('id')->toArray();

        // If no units exist, use nulls
        $buyingUnitId = $units[0] ?? null;
        $sellingUnitId = $units[1] ?? $buyingUnitId;

        return [
            'tenant_id' => $tenant->id,
            'name' => fake()->words(3, true),
            'code' => 'PRD-'.fake()->unique()->numerify('######'),
            'type' => fake()->randomElement([
                ProductType::GOOD->value,
                ProductType::SERVICE->value,
            ]),
            'description' => fake()->paragraph(),
            'category_id' => null, // Will be set if needed
            'buying_unit_id' => $buyingUnitId,
            'selling_unit_id' => $sellingUnitId,
            'metadata' => [
                'sku' => fake()->unique()->ean13(),
                'barcode' => fake()->unique()->ean8(),
                'manufacturer' => fake()->company(),
            ],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific tenant for the product.
     */
    public function forTenant(Tenant|string $tenant): static
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create as a specific product type.
     */
    public function asType(ProductType|string $type): static
    {
        $typeValue = $type instanceof ProductType ? $type->value : $type;

        return $this->state(fn (array $attributes) => [
            'type' => $typeValue,
        ]);
    }

    /**
     * Create as a good.
     */
    public function asGood(): static
    {
        return $this->asType(ProductType::GOOD);
    }

    /**
     * Create as a service.
     */
    public function asService(): static
    {
        return $this->asType(ProductType::SERVICE);
    }

    /**
     * Create as a bundle.
     */
    public function asBundle(): static
    {
        return $this->asType(ProductType::BUNDLE);
    }

    /**
     * Create as a composite.
     */
    public function asComposite(): static
    {
        return $this->asType(ProductType::COMPOSITE);
    }

    /**
     * Assign to a category.
     */
    public function inCategory(ProductCategory|string $category): static
    {
        $categoryId = $category instanceof ProductCategory ? $category->id : $category;

        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }
}
