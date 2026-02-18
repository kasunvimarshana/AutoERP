<?php

namespace Database\Factories\Inventory;

use App\Enums\ProductType;
use App\Models\Tenant;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Inventory\Models\Product>
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
        $buyingPrice = fake()->randomFloat(2, 10, 1000);
        $sellingPrice = $buyingPrice * fake()->randomFloat(2, 1.2, 2.0);

        return [
            'tenant_id' => Tenant::factory(),
            'sku' => 'SKU-'.fake()->unique()->numerify('######'),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(ProductType::cases())->value,
            'description' => fake()->sentence(),
            'barcode' => fake()->ean13(),
            'buying_price' => $buyingPrice,
            'selling_price' => $sellingPrice,
            'mrp' => $sellingPrice * 1.1,
            'track_inventory' => true,
            'track_batch' => false,
            'track_serial' => false,
            'track_expiry' => false,
            'reorder_level' => fake()->numberBetween(10, 50),
            'min_stock_level' => fake()->numberBetween(5, 20),
            'max_stock_level' => fake()->numberBetween(100, 500),
            'category_id' => null,
            'brand_id' => null,
            'unit_id' => null,
            'tax_id' => null,
            'is_active' => true,
            'attributes' => [],
            'settings' => [],
        ];
    }

    /**
     * Indicate that the product is a service.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::SERVICE->value,
            'track_inventory' => false,
            'track_batch' => false,
            'track_serial' => false,
            'track_expiry' => false,
        ]);
    }

    /**
     * Indicate that the product is an inventory item.
     */
    public function inventory(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::INVENTORY->value,
            'track_inventory' => true,
        ]);
    }

    /**
     * Indicate that the product tracks batches.
     */
    public function withBatchTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_batch' => true,
        ]);
    }

    /**
     * Indicate that the product tracks serial numbers.
     */
    public function withSerialTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_serial' => true,
        ]);
    }

    /**
     * Indicate that the product tracks expiry dates.
     */
    public function withExpiryTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_expiry' => true,
        ]);
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
     * Set specific tenant for the product.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
