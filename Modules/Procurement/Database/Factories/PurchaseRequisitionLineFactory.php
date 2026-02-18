<?php

declare(strict_types=1);

namespace Modules\Procurement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Entities\PurchaseRequisitionLine;

class PurchaseRequisitionLineFactory extends Factory
{
    protected $model = PurchaseRequisitionLine::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->randomFloat(2, 10, 1000);

        return [
            'tenant_id' => 1,
            'purchase_requisition_id' => 1,
            'product_id' => 1,
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_of_measure' => fake()->randomElement(['ea', 'kg', 'lb', 'm', 'ft']),
            'estimated_unit_price' => $unitPrice,
            'estimated_total' => $quantity * $unitPrice,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
