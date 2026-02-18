<?php

declare(strict_types=1);

namespace Modules\Procurement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Entities\PurchaseRequisition;

class PurchaseRequisitionFactory extends Factory
{
    protected $model = PurchaseRequisition::class;

    public function definition(): array
    {
        $requestedDate = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'tenant_id' => 1,
            'requisition_number' => 'PR-'.date('Y').'-'.fake()->unique()->numerify('#####'),
            'requester_id' => 1,
            'department' => fake()->randomElement(['IT', 'HR', 'Finance', 'Operations', 'Sales']),
            'requested_date' => $requestedDate,
            'required_date' => fake()->dateTimeBetween($requestedDate, '+30 days'),
            'status' => fake()->randomElement(['draft', 'submitted', 'approved', 'rejected']),
            'approval_status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'supplier_id' => null,
            'currency' => 'USD',
            'total_amount' => fake()->randomFloat(2, 100, 50000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
