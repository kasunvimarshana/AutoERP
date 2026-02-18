<?php

namespace Database\Factories\Finance;

use App\Models\Tenant;
use App\Modules\Finance\Enums\JournalEntryStatus;
use App\Modules\Finance\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Finance\Models\JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'entry_number' => 'JE-'.fake()->unique()->numerify('######'),
            'entry_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'reference_type' => null,
            'reference_id' => null,
            'description' => fake()->optional()->sentence(),
            'status' => JournalEntryStatus::DRAFT->value,
            'posted_by' => null,
            'posted_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JournalEntryStatus::DRAFT->value,
            'posted_by' => null,
            'posted_at' => null,
        ]);
    }

    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JournalEntryStatus::POSTED->value,
            'posted_at' => now(),
        ]);
    }

    public function void(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JournalEntryStatus::VOID->value,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
