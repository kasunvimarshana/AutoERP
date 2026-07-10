<?php

declare(strict_types=1);

namespace Modules\Finance\Validators;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\CreateAccountData;
use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountCategory;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinanceDimension;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Models\FinancePostingProfile;

final class FinanceValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function validateAccountCreation(CreateAccountData $data, ?int $ignoreAccountId = null): void
    {
        if ($data->tenantId < 1) {
            throw new InvalidArgumentException('Finance account tenant is required.');
        }

        if (trim($data->code) === '' || trim($data->name) === '') {
            throw new InvalidArgumentException('Finance account code and name are required.');
        }

        $duplicate = FinanceAccount::query()
            ->where('tenant_id', $data->tenantId)
            ->where('code', trim($data->code))
            ->when($ignoreAccountId !== null, fn ($query) => $query->whereKeyNot($ignoreAccountId))
            ->exists();
        if ($duplicate) {
            throw new InvalidArgumentException('Finance account code already exists for this tenant.');
        }

        $accountType = FinanceAccountType::query()->findOrFail($data->accountTypeId);
        if ($accountType->tenant_id !== null && (int) $accountType->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException('Finance account type belongs to a different tenant.');
        }

        if ($data->accountCategoryId !== null) {
            $category = FinanceAccountCategory::query()->findOrFail($data->accountCategoryId);
            if (($category->tenant_id !== null && (int) $category->tenant_id !== $data->tenantId)
                || (int) $category->account_type_id !== $data->accountTypeId) {
                throw new InvalidArgumentException('Finance account category is invalid for the selected type and tenant.');
            }
        }

        if ($data->parentId !== null) {
            $parent = FinanceAccount::query()->findOrFail($data->parentId);
            $this->assertSameScope($data->tenantId, $data->organizationUnitId, (int) $parent->tenant_id, $parent->organization_unit_id);
            if ($ignoreAccountId !== null && (int) $parent->getKey() === $ignoreAccountId) {
                throw new InvalidArgumentException('Finance account cannot be its own parent.');
            }
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

            if ($line->dimensionId !== null) {
                $dimension = FinanceDimension::query()->findOrFail($line->dimensionId);
                $this->assertSameScope(
                    $data->tenantId,
                    $data->organizationUnitId,
                    (int) $dimension->tenant_id,
                    $dimension->organization_unit_id,
                );
                if (! (bool) $dimension->is_active) {
                    throw new InvalidArgumentException('Journal dimension must be active.');
                }
            }
        }

        if ($data->source !== null) {
            if ($data->source->tenantId !== null && $data->source->tenantId !== $data->tenantId) {
                throw new InvalidArgumentException('Journal source tenant scope mismatch.');
            }
            if ($data->source->organizationUnitId !== null
                && $data->source->organizationUnitId !== $data->organizationUnitId) {
                throw new InvalidArgumentException('Journal source organization unit scope mismatch.');
            }
        }

        if ($data->postingProfileId !== null) {
            $profile = FinancePostingProfile::query()->findOrFail($data->postingProfileId);
            $this->assertSameScope(
                $data->tenantId,
                $data->organizationUnitId,
                (int) $profile->tenant_id,
                $profile->organization_unit_id,
            );
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
