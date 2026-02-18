<?php

declare(strict_types=1);

namespace Modules\Procurement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Entities\GoodsReceipt;

class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'receipt_number' => 'GR-'.date('Y').'-'.fake()->unique()->numerify('#####'),
            'purchase_order_id' => 1,
            'received_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'received_by' => 1,
            'status' => fake()->randomElement(['draft', 'confirmed', 'cancelled']),
            'matched_status' => fake()->randomElement(['unmatched', 'partial', 'matched']),
            'warehouse_id' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
