<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Entities\JournalEntry;

class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'entry_number' => 'JE-'.$this->faker->unique()->numerify('######'),
            'entry_date' => $this->faker->date(),
            'reference' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'fiscal_period_id' => 1,
            'status' => 'draft',
            'total_debit' => 0,
            'total_credit' => 0,
            'currency' => 'USD',
            'tags' => [],
        ];
    }
}
