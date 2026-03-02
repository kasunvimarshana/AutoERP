<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Application\DTOs\CreateFiscalPeriodDTO;
use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AccountingService fiscal period methods.
 *
 * Tests listFiscalPeriods() delegation, createFiscalPeriod() method existence,
 * and closeFiscalPeriod() method existence.
 * No database or Laravel bootstrap required.
 */
class AccountingServiceFiscalPeriodTest extends TestCase
{
    private function makeService(
        ?JournalEntryRepositoryContract $journalRepo = null,
        ?AccountRepositoryContract $accountRepo = null,
        ?FiscalPeriodRepositoryContract $fiscalPeriodRepo = null,
    ): AccountingService {
        return new AccountingService(
            $journalRepo     ?? $this->createMock(JournalEntryRepositoryContract::class),
            $accountRepo     ?? $this->createMock(AccountRepositoryContract::class),
            $fiscalPeriodRepo ?? $this->createMock(FiscalPeriodRepositoryContract::class),
        );
    }

    // -------------------------------------------------------------------------
    // listFiscalPeriods — delegates to fiscalPeriodRepository->all()
    // -------------------------------------------------------------------------

    public function test_list_fiscal_periods_delegates_to_repository_all(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(FiscalPeriodRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($expected);

        $result = $this->makeService(fiscalPeriodRepo: $repo)->listFiscalPeriods();

        $this->assertSame($expected, $result);
    }

    public function test_list_fiscal_periods_returns_collection(): void
    {
        $repo = $this->createMock(FiscalPeriodRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $result = $this->makeService(fiscalPeriodRepo: $repo)->listFiscalPeriods();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_list_fiscal_periods_returns_empty_when_none_exist(): void
    {
        $repo = $this->createMock(FiscalPeriodRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $result = $this->makeService(fiscalPeriodRepo: $repo)->listFiscalPeriods();

        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // Method existence — createFiscalPeriod, closeFiscalPeriod
    // -------------------------------------------------------------------------

    public function test_accounting_service_has_create_fiscal_period_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'createFiscalPeriod'),
            'AccountingService must expose a public createFiscalPeriod() method.'
        );
    }

    public function test_create_fiscal_period_accepts_dto(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'createFiscalPeriod');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreateFiscalPeriodDTO::class, (string) $params[0]->getType());
    }

    public function test_accounting_service_has_close_fiscal_period_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'closeFiscalPeriod'),
            'AccountingService must expose a public closeFiscalPeriod() method.'
        );
    }

    public function test_close_fiscal_period_accepts_period_id_int(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'closeFiscalPeriod');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('periodId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // Controller method existence
    // -------------------------------------------------------------------------

    public function test_accounting_service_has_list_fiscal_periods_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'listFiscalPeriods'),
            'AccountingService must expose a public listFiscalPeriods() method.'
        );
    }

    // -------------------------------------------------------------------------
    // DTO payload mapping
    // -------------------------------------------------------------------------

    public function test_create_fiscal_period_dto_maps_all_fields(): void
    {
        $dto = CreateFiscalPeriodDTO::fromArray([
            'name'       => 'FY2026-Q1',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-03-31',
            'is_closed'  => false,
        ]);

        $payload = [
            'name'       => $dto->name,
            'start_date' => $dto->startDate,
            'end_date'   => $dto->endDate,
            'is_closed'  => $dto->isClosed,
        ];

        $this->assertSame('FY2026-Q1', $payload['name']);
        $this->assertSame('2026-01-01', $payload['start_date']);
        $this->assertSame('2026-03-31', $payload['end_date']);
        $this->assertFalse($payload['is_closed']);
    }
}
