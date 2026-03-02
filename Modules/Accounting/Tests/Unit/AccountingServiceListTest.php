<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AccountingService — list/read delegation paths.
 *
 * These tests are pure PHP (no database / no Laravel bootstrap).
 * The write paths (createJournalEntry, postEntry) that use DB::transaction()
 * are covered by the double-entry balance tests and feature tests.
 * This test class focuses on the read delegation and structural assertions.
 */
class AccountingServiceListTest extends TestCase
{
    private function makeService(
        ?JournalEntryRepositoryContract $journalRepo = null,
        ?AccountRepositoryContract $accountRepo = null,
    ): AccountingService {
        return new AccountingService(
            $journalRepo ?? $this->createMock(JournalEntryRepositoryContract::class),
            $accountRepo ?? $this->createMock(AccountRepositoryContract::class),
            $this->createMock(FiscalPeriodRepositoryContract::class),
        );
    }

    // -------------------------------------------------------------------------
    // listEntries — delegates to journalEntryRepository->all()
    // -------------------------------------------------------------------------

    public function test_list_entries_delegates_to_repository_all(): void
    {
        $collection = new Collection();

        $journalRepo = $this->createMock(JournalEntryRepositoryContract::class);
        $journalRepo->expects($this->once())
            ->method('all')
            ->willReturn($collection);

        $result = $this->makeService($journalRepo)->listEntries();

        $this->assertSame($collection, $result);
    }

    public function test_list_entries_returns_collection_type(): void
    {
        $journalRepo = $this->createMock(JournalEntryRepositoryContract::class);
        $journalRepo->method('all')->willReturn(new Collection());

        $result = $this->makeService($journalRepo)->listEntries();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_list_entries_accepts_empty_filter_array(): void
    {
        $collection = new Collection();

        $journalRepo = $this->createMock(JournalEntryRepositoryContract::class);
        $journalRepo->expects($this->once())
            ->method('all')
            ->willReturn($collection);

        // Passing explicit empty filters should still call all()
        $result = $this->makeService($journalRepo)->listEntries([]);

        $this->assertSame($collection, $result);
    }

    public function test_list_entries_accepts_filter_array_with_values(): void
    {
        $collection = new Collection();

        $journalRepo = $this->createMock(JournalEntryRepositoryContract::class);
        $journalRepo->expects($this->once())
            ->method('all')
            ->willReturn($collection);

        // The current implementation delegates to all() regardless of filters
        $result = $this->makeService($journalRepo)->listEntries(['status' => 'posted']);

        $this->assertSame($collection, $result);
    }

    // -------------------------------------------------------------------------
    // Service is injectable with both repository contracts
    // -------------------------------------------------------------------------

    public function test_service_accepts_both_repository_contracts(): void
    {
        $journalRepo      = $this->createMock(JournalEntryRepositoryContract::class);
        $accountRepo      = $this->createMock(AccountRepositoryContract::class);
        $fiscalPeriodRepo = $this->createMock(FiscalPeriodRepositoryContract::class);

        $service = new AccountingService($journalRepo, $accountRepo, $fiscalPeriodRepo);

        $this->assertInstanceOf(AccountingService::class, $service);
    }
}
