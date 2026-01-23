<?php

declare(strict_types=1);

namespace Modules\Invoice\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Invoice\Models\DriverCommission;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Invoice\Models\DriverCommission>
 */
class DriverCommissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DriverCommission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $commissionRate = $this->faker->randomFloat(2, 1, 15);
        $commissionAmount = $this->faker->randomFloat(2, 10, 500);

        return [
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'status' => $this->faker->randomElement(['pending', 'approved', 'paid']),
            'paid_date' => $this->faker->optional()->date(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
