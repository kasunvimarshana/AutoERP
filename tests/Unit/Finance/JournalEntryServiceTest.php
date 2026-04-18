<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use Modules\Finance\Application\Services\CreateJournalEntryService;
use Modules\Finance\Domain\Entities\FiscalPeriod;
use Modules\Finance\Domain\Exceptions\FiscalPeriodNotFoundException;
use Modules\Finance\Domain\Exceptions\UnbalancedJournalEntryException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class JournalEntryServiceTest extends TestCase
{
    /** @var JournalEntryRepositoryInterface&MockObject */
    private JournalEntryRepositoryInterface $journalEntryRepository;

    /** @var FiscalPeriodRepositoryInterface&MockObject */
    private FiscalPeriodRepositoryInterface $fiscalPeriodRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalEntryRepository = $this->createMock(JournalEntryRepositoryInterface::class);
        $this->fiscalPeriodRepository = $this->createMock(FiscalPeriodRepositoryInterface::class);
    }

    public function test_create_journal_entry_throws_when_period_is_missing_or_closed(): void
    {
        $service = new CreateJournalEntryService($this->journalEntryRepository, $this->fiscalPeriodRepository);

        $this->fiscalPeriodRepository
            ->expects($this->once())
            ->method('find')
            ->with(77)
            ->willReturn(null);

        $this->journalEntryRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(FiscalPeriodNotFoundException::class);

        $service->execute([
            'tenant_id' => 1,
            'fiscal_period_id' => 77,
            'entry_date' => '2026-04-19',
            'created_by' => 10,
            'lines' => [
                ['account_id' => 1, 'debit_amount' => 100, 'credit_amount' => 0],
                ['account_id' => 2, 'debit_amount' => 0, 'credit_amount' => 100],
            ],
        ]);
    }

    public function test_create_journal_entry_throws_when_not_balanced(): void
    {
        $service = new CreateJournalEntryService($this->journalEntryRepository, $this->fiscalPeriodRepository);

        $this->fiscalPeriodRepository
            ->expects($this->once())
            ->method('find')
            ->with(88)
            ->willReturn(new FiscalPeriod(
                tenantId: 1,
                fiscalYearId: 1,
                periodNumber: 1,
                name: 'P1',
                startDate: new \DateTimeImmutable('2026-01-01'),
                endDate: new \DateTimeImmutable('2026-01-31'),
                status: 'open',
                id: 88,
            ));

        $this->journalEntryRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(UnbalancedJournalEntryException::class);

        $service->execute([
            'tenant_id' => 1,
            'fiscal_period_id' => 88,
            'entry_date' => '2026-04-19',
            'created_by' => 10,
            'lines' => [
                ['account_id' => 1, 'debit_amount' => 100, 'credit_amount' => 0],
                ['account_id' => 2, 'debit_amount' => 0, 'credit_amount' => 90],
            ],
        ]);
    }
}
