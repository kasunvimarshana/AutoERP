<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Mockery;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\FiscalPeriodRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Services\JournalEntryService;
use PHPUnit\Framework\TestCase;

class JournalEntryServiceTest extends TestCase
{
    protected JournalEntryService $journalEntryService;

    protected $journalEntryRepository;

    protected $lineRepository;

    protected $accountRepository;

    protected $fiscalPeriodRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->journalEntryRepository = Mockery::mock(JournalEntryRepositoryInterface::class);
        $this->lineRepository = Mockery::mock(JournalEntryLineRepositoryInterface::class);
        $this->accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $this->fiscalPeriodRepository = Mockery::mock(FiscalPeriodRepositoryInterface::class);

        $this->journalEntryService = new JournalEntryService(
            $this->journalEntryRepository,
            $this->lineRepository,
            $this->accountRepository,
            $this->fiscalPeriodRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_validates_balanced_entry(): void
    {
        $entry = new JournalEntry([
            'total_debit' => 1000,
            'total_credit' => 1000,
        ]);

        $result = $this->journalEntryService->validateBalance($entry);

        $this->assertTrue($result);
    }

    public function test_it_detects_unbalanced_entry(): void
    {
        $entry = new JournalEntry([
            'total_debit' => 1000,
            'total_credit' => 500,
        ]);

        $result = $this->journalEntryService->validateBalance($entry);

        $this->assertFalse($result);
    }
}
