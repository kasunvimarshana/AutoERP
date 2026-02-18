<?php

namespace Tests\Feature\Finance;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Finance\Enums\JournalEntryStatus;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\FiscalYear;
use App\Modules\Finance\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JournalEntryApiTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUri = '/api/v1/finance/journal-entries';

    private Tenant $tenant;

    private User $user;

    private Account $debitAccount;

    private Account $creditAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($this->user);

        FiscalYear::factory()->forTenant($this->tenant)->create([
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_closed' => false,
        ]);

        $this->debitAccount = Account::factory()->forTenant($this->tenant)->asset()->create();
        $this->creditAccount = Account::factory()->forTenant($this->tenant)->revenue()->create();
    }

    public function test_can_create_balanced_journal_entry(): void
    {
        $data = [
            'entry_number' => 'JE-001',
            'entry_date' => now()->toDateString(),
            'description' => 'Test entry',
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ];

        $response = $this->postJson($this->baseUri, $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'entry_number',
                    'lines' => [
                        '*' => ['account_id', 'debit', 'credit'],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('journal_entries', [
            'entry_number' => 'JE-001',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_cannot_create_unbalanced_journal_entry(): void
    {
        $data = [
            'entry_number' => 'JE-002',
            'entry_date' => now()->toDateString(),
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 500, // Unbalanced!
                ],
            ],
        ];

        $response = $this->postJson($this->baseUri, $data);

        $response->assertStatus(500); // Should fail validation
    }

    public function test_can_post_journal_entry(): void
    {
        $entry = JournalEntry::factory()->forTenant($this->tenant)->create([
            'status' => JournalEntryStatus::DRAFT,
        ]);

        $entry->lines()->create([
            'account_id' => $this->debitAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $entry->lines()->create([
            'account_id' => $this->creditAccount->id,
            'debit' => 0,
            'credit' => 1000,
        ]);

        $response = $this->postJson("{$this->baseUri}/{$entry->id}/post");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => JournalEntryStatus::POSTED->value,
                ],
            ]);

        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'status' => JournalEntryStatus::POSTED->value,
        ]);
    }

    public function test_can_void_posted_journal_entry(): void
    {
        $entry = JournalEntry::factory()->forTenant($this->tenant)->posted()->create();

        $entry->lines()->create([
            'account_id' => $this->debitAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $entry->lines()->create([
            'account_id' => $this->creditAccount->id,
            'debit' => 0,
            'credit' => 1000,
        ]);

        $response = $this->postJson("{$this->baseUri}/{$entry->id}/void");

        $response->assertStatus(200);

        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'status' => JournalEntryStatus::VOID->value,
        ]);
    }

    public function test_can_list_journal_entries(): void
    {
        JournalEntry::factory()->forTenant($this->tenant)->count(3)->create();

        $response = $this->getJson($this->baseUri);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'entry_number', 'status'],
                    ],
                ],
            ]);
    }

    public function test_can_generate_entry_number(): void
    {
        $response = $this->getJson("{$this->baseUri}/generate-entry-number");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'entry_number',
                ],
            ]);
    }
}
