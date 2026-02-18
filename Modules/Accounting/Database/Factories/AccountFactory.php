<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Entities\Account;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        $type = $this->faker->randomElement($types);

        return [
            'tenant_id' => 1,
            'account_number' => $this->faker->unique()->numerify('####'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $type,
            'sub_type' => $this->faker->word(),
            'currency' => 'USD',
            'is_active' => true,
            'is_system' => false,
            'balance' => $this->faker->randomFloat(2, 0, 100000),
            'allow_manual_entries' => true,
            'tags' => [],
        ];
    }
}
