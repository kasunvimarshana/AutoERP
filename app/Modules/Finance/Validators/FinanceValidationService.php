<?php

declare(strict_types=1);

namespace Modules\Finance\Validators;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\Enums\FiscalPeriodStatus;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceFiscalPeriod;
use Modules\Finance\Models\FinanceJournalEntry;

final class FinanceValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function validateAccountCreation(CreateAccountData $data): void
    {
        if ($data->tenantId < 1) {
            throw new InvalidArgumentException('Finance account tenant is required.');
        }

        if (trim($data->code) === '' || trim($data->name) === '') {
            throw new InvalidArgumentException('Finance account code and name are required.');
        }

        $this->assertNonNegative($data->openingBalance, 'Opening balance');

        if ($data->parentId !== null) {
            $parent = FinanceAccount::query()->findOrFail($data->parentId);
            $this->assertSameScope($data->tenantId, $data->organizationUnitId, (int) $parent->tenant_id, $parent->organization_unit_id);
        }
    }

    public function validateJournalCreation(CreateJournalEntryData $data): void
    {
        if ($data->tenantId < 1) {
            throw new InvalidArgumentException('Journal tenant is required.');
        }

        if (trim($data->journalDate) === '') {
            throw new InvalidArgumentException('Journal date is required.');
        }

        if ($data->lines === []) {
            throw new InvalidArgumentException('Journal requires at least one line.');
        }

        if ($this->math->isNegative($data->exchangeRate) || $this->math->isZero($data->exchangeRate)) {
            throw new InvalidArgumentException('Journal exchange rate must be greater than zero.');
        }

        foreach ($data->lines as $line) {
            if (! $line instanceof JournalLineData) {
                throw new InvalidArgumentException('Journal lines must be JournalLineData instances.');
            }

            $this->validateJournalLine($line);
            $account = FinanceAccount::query()->findOrFail($line->accountId);
            $this->assertSameScope($data->tenantId, $data->organizationUnitId, (int) $account->tenant_id, $account->organization_unit_id);
        }

        [$totalDebit, $totalCredit] = $this->journalTotals($data->lines);
        if ($this->math->compare($totalDebit, $totalCredit) !== 0) {
            throw new InvalidArgumentException('Journal must be balanced before it can be created.');
        }
    }

    public function validateForPosting(FinanceJournalEntry $journal): void
    {
        $status = $journal->status instanceof JournalStatus
            ? $journal->status
            : JournalStatus::from((string) $journal->status);

        if ($status !== JournalStatus::Draft) {
            throw new InvalidArgumentException('Only draft journals can be posted.');
        }

        if ($this->math->compare((string) $journal->total_debit, (string) $journal->total_credit) !== 0) {
            throw new InvalidArgumentException('Unbalanced journal cannot be posted.');
        }

        if ($journal->fiscalPeriod instanceof FinanceFiscalPeriod) {
            $periodStatus = $journal->fiscalPeriod->status instanceof FiscalPeriodStatus
                ? $journal->fiscalPeriod->status
                : FiscalPeriodStatus::from((string) $journal->fiscalPeriod->status);

            if ($periodStatus !== FiscalPeriodStatus::Open) {
                throw new InvalidArgumentException('Cannot post into a closed or locked fiscal period.');
            }
        }

        foreach ($journal->lines as $line) {
            $this->validateJournalLine(new JournalLineData(
                accountId: (int) $line->account_id,
                lineNumber: (int) $line->line_number,
                debit: (string) $line->debit,
                credit: (string) $line->credit,
            ));

            $account = $line->account;
            if (! (bool) $account->is_active) {
                throw new InvalidArgumentException('Cannot post to inactive account.');
            }

            if (! (bool) $account->is_posting_account) {
                throw new InvalidArgumentException('Cannot post to non-posting account.');
            }

            $this->assertSameScope((int) $journal->tenant_id, $journal->organization_unit_id, (int) $account->tenant_id, $account->organization_unit_id);
        }
    }

    public function assertReversible(FinanceJournalEntry $journal): void
    {
        $status = $journal->status instanceof JournalStatus
            ? $journal->status
            : JournalStatus::from((string) $journal->status);

        if ($status !== JournalStatus::Posted) {
            throw new InvalidArgumentException('Only posted journals can be reversed.');
        }

        if ($journal->reversals()->exists()) {
            throw new InvalidArgumentException('Journal has already been reversed.');
        }
    }

    /**
     * @param  list<JournalLineData>  $lines
     * @return array{0: string, 1: string}
     */
    public function journalTotals(array $lines): array
    {
        $totalDebit = '0.000000';
        $totalCredit = '0.000000';

        foreach ($lines as $line) {
            $totalDebit = $this->math->add($totalDebit, $line->debit);
            $totalCredit = $this->math->add($totalCredit, $line->credit);
        }

        return [$totalDebit, $totalCredit];
    }

    private function validateJournalLine(JournalLineData $line): void
    {
        if ($line->lineNumber < 1) {
            throw new InvalidArgumentException('Journal line number must be positive.');
        }

        $this->assertNonNegative($line->debit, 'Journal line debit');
        $this->assertNonNegative($line->credit, 'Journal line credit');

        $hasDebit = ! $this->math->isZero($line->debit);
        $hasCredit = ! $this->math->isZero($line->credit);

        if ($hasDebit && $hasCredit) {
            throw new InvalidArgumentException('Journal line cannot have both debit and credit.');
        }

        if (! $hasDebit && ! $hasCredit) {
            throw new InvalidArgumentException('Journal line must have either debit or credit.');
        }
    }

    private function assertSameScope(int $tenantId, ?int $organizationUnitId, int $targetTenantId, ?int $targetOrganizationUnitId): void
    {
        if ($tenantId !== $targetTenantId) {
            throw new InvalidArgumentException('Finance posting tenant scope mismatch.');
        }

        if ($organizationUnitId !== $targetOrganizationUnitId) {
            throw new InvalidArgumentException('Finance posting organization unit scope mismatch.');
        }
    }

    private function assertNonNegative(string $amount, string $label): void
    {
        if ($this->math->isNegative($amount)) {
            throw new InvalidArgumentException($label.' cannot be negative.');
        }
    }
}
