<?php

declare(strict_types=1);

namespace Modules\Procurement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Entities\Supplier;

/**
 * Supplier Factory
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'code' => 'SUP-'.fake()->unique()->numerify('#####'),
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'tax_id' => fake()->optional()->numerify('TAX-########'),
            'payment_terms' => fake()->randomElement(['Net 30', 'Net 60', 'Net 90', 'COD', '2/10 Net 30']),
            'credit_limit' => fake()->randomFloat(2, 10000, 1000000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'rating' => fake()->randomFloat(1, 0, 5),
            'status' => fake()->randomElement(['active', 'inactive', 'suspended']),
            'notes' => fake()->optional()->sentence(),
            'internal_notes' => fake()->optional()->sentence(),
        ];
    }
}
