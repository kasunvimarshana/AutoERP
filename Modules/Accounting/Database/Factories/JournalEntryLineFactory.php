<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Entities\JournalEntryLine;

class JournalEntryLineFactory extends Factory
{
    protected $model = JournalEntryLine::class;

    public function definition(): array
    {
        $isDebit = $this->faker->boolean();
        $amount = $this->faker->randomFloat(2, 100, 10000);

        return [
            'tenant_id' => 1,
            'journal_entry_id' => 1,
            'account_id' => 1,
            'description' => $this->faker->sentence(),
            'debit_amount' => $isDebit ? $amount : 0,
            'credit_amount' => $isDebit ? 0 : $amount,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'tags' => [],
        ];
    }
}
