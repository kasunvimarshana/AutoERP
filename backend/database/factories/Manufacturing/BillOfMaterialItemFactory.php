<?php

namespace Database\Factories\Manufacturing;

use App\Modules\Inventory\Models\Product;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use App\Modules\Manufacturing\Models\BillOfMaterialItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Manufacturing\Models\BillOfMaterialItem>
 */
class BillOfMaterialItemFactory extends Factory
{
    protected $model = BillOfMaterialItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'bill_of_material_id' => BillOfMaterial::factory(),
            'component_product_id' => Product::factory(),
            'quantity' => fake()->randomFloat(4, 0.1, 100),
            'uom_id' => null,
            'scrap_factor' => fake()->randomFloat(2, 0, 5),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Set specific BOM
     */
    public function forBOM(BillOfMaterial $bom): static
    {
        return $this->state(fn (array $attributes) => [
            'bill_of_material_id' => $bom->id,
        ]);
    }

    /**
     * Set specific component product
     */
    public function forComponent(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'component_product_id' => $product->id,
        ]);
    }
}
