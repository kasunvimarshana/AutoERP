<?php

namespace Tests\Unit\Finance;

use App\Models\Tenant;
use App\Modules\Finance\DTOs\CreateJournalEntryDTO;
use App\Modules\Finance\Enums\JournalEntryStatus;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\FiscalYear;
use App\Modules\Finance\Models\JournalEntry;
use App\Modules\Finance\Repositories\AccountRepository;
use App\Modules\Finance\Repositories\FiscalYearRepository;
use App\Modules\Finance\Repositories\JournalEntryRepository;
use App\Modules\Finance\Services\AccountService;
use App\Modules\Finance\Services\JournalEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JournalEntryService $service;

    protected AccountService $accountService;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->accountService = new AccountService(new AccountRepository);
        $this->service = new JournalEntryService(
            new JournalEntryRepository,
            new AccountRepository,
            new FiscalYearRepository,
            $this->accountService
        );

        $this->actingAs($this->createUser($this->tenant));
    }

    public function test_validates_balanced_entry(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total debits');

        $debitAccount = Account::factory()->forTenant($this->tenant)->asset()->create();
        $creditAccount = Account::factory()->forTenant($this->tenant)->revenue()->create();

        FiscalYear::factory()->forTenant($this->tenant)->create([
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_closed' => false,
        ]);

        $dto = new CreateJournalEntryDTO(
            entryNumber: 'JE-001',
            entryDate: now()->toDateString(),
            lines: [
                [
                    'account_id' => $debitAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 500, // Unbalanced!
                ],
            ],
        );

        $this->service->createJournalEntry($dto);
    }

    public function test_can_create_balanced_journal_entry(): void
    {
        $debitAccount = Account::factory()->forTenant($this->tenant)->asset()->create();
        $creditAccount = Account::factory()->forTenant($this->tenant)->revenue()->create();

        FiscalYear::factory()->forTenant($this->tenant)->create([
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_closed' => false,
        ]);

        $dto = new CreateJournalEntryDTO(
            entryNumber: 'JE-001',
            entryDate: now()->toDateString(),
            lines: [
                [
                    'account_id' => $debitAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        );

        $entry = $this->service->createJournalEntry($dto);

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertEquals('JE-001', $entry->entry_number);
        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(JournalEntryStatus::DRAFT, $entry->status);
    }

    public function test_can_post_journal_entry(): void
    {
        $debitAccount = Account::factory()->forTenant($this->tenant)->asset()->create();
        $creditAccount = Account::factory()->forTenant($this->tenant)->revenue()->create();

        FiscalYear::factory()->forTenant($this->tenant)->create([
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_closed' => false,
        ]);

        $dto = new CreateJournalEntryDTO(
            entryNumber: 'JE-002',
            entryDate: now()->toDateString(),
            lines: [
                [
                    'account_id' => $debitAccount->id,
                    'debit' => 2000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 2000,
                ],
            ],
        );

        $entry = $this->service->createJournalEntry($dto);
        $postedEntry = $this->service->postJournalEntry($entry->id);

        $this->assertEquals(JournalEntryStatus::POSTED, $postedEntry->status);
        $this->assertNotNull($postedEntry->posted_at);
        $this->assertNotNull($postedEntry->posted_by);
    }

    public function test_cannot_post_unbalanced_entry(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $debitAccount = Account::factory()->forTenant($this->tenant)->asset()->create();

        FiscalYear::factory()->forTenant($this->tenant)->create([
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_closed' => false,
        ]);

        $entry = JournalEntry::factory()->forTenant($this->tenant)->create([
            'entry_number' => 'JE-003',
            'status' => JournalEntryStatus::DRAFT,
        ]);

        // Create unbalanced lines manually
        $entry->lines()->create([
            'account_id' => $debitAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $this->service->postJournalEntry($entry->id);
    }

    public function test_can_void_posted_entry(): void
    {
        $debitAccount = Account::factory()->forTenant($this->tenant)->asset()->create();
        $creditAccount = Account::factory()->forTenant($this->tenant)->revenue()->create();

        FiscalYear::factory()->forTenant($this->tenant)->create([
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_closed' => false,
        ]);

        $dto = new CreateJournalEntryDTO(
            entryNumber: 'JE-004',
            entryDate: now()->toDateString(),
            lines: [
                [
                    'account_id' => $debitAccount->id,
                    'debit' => 1500,
                    'credit' => 0,
                ],
                [
                    'account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => 1500,
                ],
            ],
        );

        $entry = $this->service->createJournalEntry($dto);
        $postedEntry = $this->service->postJournalEntry($entry->id);
        $voidedEntry = $this->service->voidJournalEntry($postedEntry->id);

        $this->assertEquals(JournalEntryStatus::VOID, $voidedEntry->status);
    }

    protected function createUser(Tenant $tenant)
    {
        return \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);
    }
}
