<?php

namespace Database\Factories\Manufacturing;

use App\Models\Tenant;
use App\Modules\Inventory\Models\Product;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Manufacturing\Models\BillOfMaterial>
 */
class BillOfMaterialFactory extends Factory
{
    protected $model = BillOfMaterial::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'product_id' => Product::factory(),
            'bom_number' => 'BOM-'.fake()->unique()->numerify('######'),
            'version' => 1,
            'is_active' => true,
            'effective_date' => now()->toDateString(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the BOM is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set specific version
     */
    public function version(int $version): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => $version,
        ]);
    }

    /**
     * Set specific tenant
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Set specific product
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'tenant_id' => $product->tenant_id,
        ]);
    }
}
