<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Application\DTOs\CreateFiscalPeriodDTO;
use Modules\Accounting\Application\DTOs\CreateJournalEntryDTO;
use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural compliance tests for AccountingService write-path methods.
 *
 * createAccount(), createFiscalPeriod(), closeFiscalPeriod(), and postEntry()
 * all invoke DB::transaction() internally, so functional tests belong in
 * feature tests. These pure-PHP tests verify method signatures and
 * DTO field-mapping contracts without requiring a database.
 */
class AccountingServiceWritePathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_accounting_service_has_create_account_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'createAccount'),
            'AccountingService must expose a public createAccount() method.'
        );
    }

    public function test_accounting_service_has_create_fiscal_period_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'createFiscalPeriod'),
            'AccountingService must expose a public createFiscalPeriod() method.'
        );
    }

    public function test_accounting_service_has_close_fiscal_period_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'closeFiscalPeriod'),
            'AccountingService must expose a public closeFiscalPeriod() method.'
        );
    }

    public function test_accounting_service_has_post_entry_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'postEntry'),
            'AccountingService must expose a public postEntry() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_create_account_accepts_data_array(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'createAccount');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
    }

    public function test_create_fiscal_period_accepts_create_fiscal_period_dto(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'createFiscalPeriod');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreateFiscalPeriodDTO::class, (string) $params[0]->getType());
    }

    public function test_close_fiscal_period_accepts_single_period_id(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'closeFiscalPeriod');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('periodId', $params[0]->getName());
    }

    public function test_post_entry_accepts_single_entry_id(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'postEntry');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('entryId', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // CreateFiscalPeriodDTO — payload mapping contract
    // -------------------------------------------------------------------------

    public function test_create_fiscal_period_dto_maps_all_fields(): void
    {
        $dto = CreateFiscalPeriodDTO::fromArray([
            'name'       => 'Q1 2026',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-03-31',
            'is_closed'  => false,
        ]);

        $payload = $dto->toArray();

        $this->assertSame('Q1 2026', $payload['name']);
        $this->assertSame('2026-01-01', $payload['start_date']);
        $this->assertSame('2026-03-31', $payload['end_date']);
        $this->assertFalse($payload['is_closed']);
    }

    public function test_create_fiscal_period_dto_is_closed_defaults_false(): void
    {
        $dto = CreateFiscalPeriodDTO::fromArray([
            'name'       => 'FY 2027',
            'start_date' => '2027-01-01',
            'end_date'   => '2027-12-31',
        ]);

        $this->assertFalse($dto->isClosed);
    }

    public function test_create_fiscal_period_dto_is_closed_true_preserved(): void
    {
        $dto = CreateFiscalPeriodDTO::fromArray([
            'name'       => 'Closed Period',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-12-31',
            'is_closed'  => true,
        ]);

        $this->assertTrue($dto->isClosed);
    }

    // -------------------------------------------------------------------------
    // CreateJournalEntryDTO — double-entry field mapping
    // -------------------------------------------------------------------------

    public function test_create_journal_entry_dto_maps_all_fields(): void
    {
        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-0001',
            'description'      => 'Test entry',
            'entry_date'       => '2026-03-01',
            'lines'            => [
                ['account_id' => 100, 'type' => 'debit',  'amount' => '1000.0000'],
                ['account_id' => 200, 'type' => 'credit', 'amount' => '1000.0000'],
            ],
        ]);

        $this->assertSame(1, $dto->fiscalPeriodId);
        $this->assertSame('JE-0001', $dto->referenceNumber);
        $this->assertSame('Test entry', $dto->description);
        $this->assertSame('2026-03-01', $dto->entryDate);
        $this->assertCount(2, $dto->lines);
    }

    // -------------------------------------------------------------------------
    // Service instantiation — structural smoke test
    // -------------------------------------------------------------------------

    public function test_accounting_service_can_be_instantiated_with_all_three_repository_contracts(): void
    {
        $service = new AccountingService(
            $this->createMock(JournalEntryRepositoryContract::class),
            $this->createMock(AccountRepositoryContract::class),
            $this->createMock(FiscalPeriodRepositoryContract::class),
        );

        $this->assertInstanceOf(AccountingService::class, $service);
    }

    public function test_write_path_methods_are_all_public(): void
    {
        $methods = ['createAccount', 'createFiscalPeriod', 'closeFiscalPeriod', 'postEntry'];

        foreach ($methods as $method) {
            $reflection = new ReflectionMethod(AccountingService::class, $method);
            $this->assertTrue(
                $reflection->isPublic(),
                "AccountingService::{$method}() must be public."
            );
        }
    }
}
