<?php

namespace Database\Factories\Finance;

use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\JournalEntry;
use App\Modules\Finance\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Finance\Models\JournalEntryLine>
 */
class JournalEntryLineFactory extends Factory
{
    protected $model = JournalEntryLine::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10, 10000);
        $isDebit = fake()->boolean();

        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'debit' => $isDebit ? $amount : 0,
            'credit' => ! $isDebit ? $amount : 0,
            'description' => fake()->optional()->sentence(),
            'cost_center_id' => null,
        ];
    }

    public function debit(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'debit' => $amount,
            'credit' => 0,
        ]);
    }

    public function credit(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'debit' => 0,
            'credit' => $amount,
        ]);
    }

    public function forJournalEntry(JournalEntry $journalEntry): static
    {
        return $this->state(fn (array $attributes) => [
            'journal_entry_id' => $journalEntry->id,
        ]);
    }

    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }
}
