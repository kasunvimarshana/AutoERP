<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use InvalidArgumentException;
use Modules\Accounting\Application\DTOs\CreateJournalEntryDTO;
use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AccountingService double-entry balance validation.
 *
 * These tests are pure PHP (no database / no Laravel bootstrap).
 * Repositories are stubbed so only the BCMath balance logic is exercised.
 */
class AccountingServiceDoubleEntryTest extends TestCase
{
    private AccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Stub the repository contracts — balance validation is pure logic.
        $journalRepo  = $this->createStub(JournalEntryRepositoryContract::class);
        $accountRepo  = $this->createStub(AccountRepositoryContract::class);
        $fiscalPeriodRepo = $this->createStub(FiscalPeriodRepositoryContract::class);

        $this->service = new AccountingService($journalRepo, $accountRepo, $fiscalPeriodRepo);
    }

    // -------------------------------------------------------------------------
    // Balanced entry validation (should NOT throw)
    // -------------------------------------------------------------------------

    public function test_balanced_entry_does_not_throw(): void
    {
        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-001',
            'description'      => 'Test balanced entry',
            'entry_date'       => '2026-01-01',
            'lines'            => [
                ['account_id' => 1, 'type' => 'debit',  'amount' => '100.0000'],
                ['account_id' => 2, 'type' => 'credit', 'amount' => '100.0000'],
            ],
        ]);

        // assertDoubleEntryBalance is tested indirectly via the service method.
        // We expect an exception only on repository interaction, not balance check.
        // Wrap in try/catch to isolate the balance check from DB/mock exceptions.
        $balanceFailed = false;
        try {
            // The balance check runs before any DB call — any Exception thrown
            // at this point that is an InvalidArgumentException is a balance failure.
            $this->service->createJournalEntry($dto);
        } catch (InvalidArgumentException $e) {
            $balanceFailed = true;
        } catch (\Throwable) {
            // Other exceptions (e.g., from stub) are acceptable here.
        }

        $this->assertFalse($balanceFailed, 'Balanced entry should not fail the double-entry check.');
    }

    public function test_balanced_entry_with_multiple_lines(): void
    {
        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-002',
            'description'      => 'Multi-line balanced entry',
            'entry_date'       => '2026-01-01',
            'lines'            => [
                ['account_id' => 1, 'type' => 'debit',  'amount' => '250.0000'],
                ['account_id' => 3, 'type' => 'debit',  'amount' => '150.0000'],
                ['account_id' => 2, 'type' => 'credit', 'amount' => '400.0000'],
            ],
        ]);

        $balanceFailed = false;
        try {
            $this->service->createJournalEntry($dto);
        } catch (InvalidArgumentException $e) {
            $balanceFailed = true;
        } catch (\Throwable) {
            // OK
        }

        $this->assertFalse($balanceFailed, 'Multi-line balanced entry should not fail the double-entry check.');
    }

    public function test_balanced_entry_with_decimal_amounts(): void
    {
        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-003',
            'description'      => 'Decimal balanced entry',
            'entry_date'       => '2026-01-15',
            'lines'            => [
                ['account_id' => 1, 'type' => 'debit',  'amount' => '99.9999'],
                ['account_id' => 2, 'type' => 'credit', 'amount' => '99.9999'],
            ],
        ]);

        $balanceFailed = false;
        try {
            $this->service->createJournalEntry($dto);
        } catch (InvalidArgumentException $e) {
            $balanceFailed = true;
        } catch (\Throwable) {
            // OK
        }

        $this->assertFalse($balanceFailed, 'Decimal balanced entry should not fail the double-entry check.');
    }

    // -------------------------------------------------------------------------
    // Unbalanced entry validation (MUST throw InvalidArgumentException)
    // -------------------------------------------------------------------------

    public function test_unbalanced_entry_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/unbalanced/i');

        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-BAD',
            'description'      => 'Unbalanced entry',
            'entry_date'       => '2026-01-01',
            'lines'            => [
                ['account_id' => 1, 'type' => 'debit',  'amount' => '100.0000'],
                ['account_id' => 2, 'type' => 'credit', 'amount' => '90.0000'],
            ],
        ]);

        $this->service->createJournalEntry($dto);
    }

    public function test_debit_exceeds_credit_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-BAD2',
            'description'      => 'Debit > Credit',
            'entry_date'       => '2026-01-01',
            'lines'            => [
                ['account_id' => 1, 'type' => 'debit',  'amount' => '500.0000'],
                ['account_id' => 2, 'type' => 'credit', 'amount' => '499.9999'],
            ],
        ]);

        $this->service->createJournalEntry($dto);
    }

    public function test_credit_exceeds_debit_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-BAD3',
            'description'      => 'Credit > Debit',
            'entry_date'       => '2026-01-01',
            'lines'            => [
                ['account_id' => 1, 'type' => 'debit',  'amount' => '200.0000'],
                ['account_id' => 2, 'type' => 'credit', 'amount' => '300.0000'],
            ],
        ]);

        $this->service->createJournalEntry($dto);
    }

    public function test_zero_debit_with_non_zero_credit_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-BAD4',
            'description'      => 'Zero debit, non-zero credit',
            'entry_date'       => '2026-01-01',
            'lines'            => [
                ['account_id' => 1, 'type' => 'debit',  'amount' => '0.0000'],
                ['account_id' => 2, 'type' => 'credit', 'amount' => '1.0000'],
            ],
        ]);

        $this->service->createJournalEntry($dto);
    }

    public function test_only_debits_no_credits_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-BAD5',
            'description'      => 'Only debits',
            'entry_date'       => '2026-01-01',
            'lines'            => [
                ['account_id' => 1, 'type' => 'debit', 'amount' => '100.0000'],
                ['account_id' => 3, 'type' => 'debit', 'amount' => '50.0000'],
            ],
        ]);

        $this->service->createJournalEntry($dto);
    }

    // -------------------------------------------------------------------------
    // BCMath precision — balance check must not use float arithmetic
    // -------------------------------------------------------------------------

    public function test_balance_check_is_float_safe(): void
    {
        // Classic float drift: 0.1 + 0.2 != 0.3 in IEEE 754.
        // With BCMath, 0.1000 + 0.2000 == 0.3000 exactly.
        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-FLOAT',
            'description'      => 'Float-safe balance check',
            'entry_date'       => '2026-01-01',
            'lines'            => [
                ['account_id' => 1, 'type' => 'debit',  'amount' => '0.1000'],
                ['account_id' => 1, 'type' => 'debit',  'amount' => '0.2000'],
                ['account_id' => 2, 'type' => 'credit', 'amount' => '0.3000'],
            ],
        ]);

        $balanceFailed = false;
        try {
            $this->service->createJournalEntry($dto);
        } catch (InvalidArgumentException $e) {
            $balanceFailed = true;
        } catch (\Throwable) {
            // OK — DB stub may throw; we only care about the balance result.
        }

        $this->assertFalse($balanceFailed, 'BCMath balance check should correctly detect 0.1+0.2==0.3 without float drift.');
    }
}
