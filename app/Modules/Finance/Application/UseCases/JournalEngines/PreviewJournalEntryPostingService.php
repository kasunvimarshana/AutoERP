<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\Services\FiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\PreviewJournalEntryPostingServiceInterface;
use Modules\Finance\Application\Repositories\AccountRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Throwable;

final class PreviewJournalEntryPostingService implements PreviewJournalEntryPostingServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly JournalEntryLineRepositoryInterface $lines,
        private readonly AccountRepositoryInterface $accounts,
        private readonly FiscalPeriodServiceInterface $fiscalPeriodService,
    ) {
    }

    public function execute(int|string $journalEntryId, array $payload): Result
    {
        try {
            $entry = $this->entries->findById($journalEntryId);
            if (! $entry instanceof DataRecord) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Journal entry not found.'));
            }

            $currentStatus = strtoupper(trim((string) $entry->get('status', JournalEntryStatus::DRAFT)));
            if (! JournalEntryStatus::canTransition($currentStatus, JournalEntryStatus::POSTED)) {
                return Result::failure(new Error(
                    FinanceErrorCode::INVALID_STATUS_TRANSITION,
                    'Journal entry cannot be posted from current status.',
                    ['current_status' => $currentStatus],
                ));
            }

            $expectedRowVersion = isset($payload['expected_row_version'])
                ? (int) $payload['expected_row_version']
                : null;
            $currentRowVersion = (int) $entry->get('row_version', 1);

            if ($expectedRowVersion !== null && $expectedRowVersion !== $currentRowVersion) {
                return Result::failure(new Error(
                    FinanceErrorCode::CONFLICT,
                    'Journal entry row version mismatch.',
                    [
                        'expected_row_version' => $expectedRowVersion,
                        'current_row_version' => $currentRowVersion,
                    ],
                ));
            }

            $lines = $this->lines->listByJournalEntry((int) $entry->id());
            if (count($lines) < 2) {
                return Result::failure(new Error(
                    FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY,
                    'Journal entry requires at least two lines for posting.',
                ));
            }

            foreach ($lines as $line) {
                $debit = (float) $line->get('debit_amount', 0);
                $credit = (float) $line->get('credit_amount', 0);
                if (($debit <= 0 && $credit <= 0) || ($debit > 0 && $credit > 0)) {
                    return Result::failure(new Error(
                        FinanceErrorCode::INVALID_JOURNAL_LINE,
                        'Each journal line must have either debit or credit amount.',
                    ));
                }

                $accountId = (int) $line->get('account_id', 0);
                $tenantId = (int) $entry->get('tenant_id');
                if ($this->accounts->findPostableById($accountId, $tenantId) === null) {
                    return Result::failure(new Error(
                        FinanceErrorCode::ACCOUNT_NOT_POSTABLE,
                        'One or more accounts are not postable.',
                        ['account_id' => $accountId],
                    ));
                }
            }

            $totals = $this->calculateTotals($lines);
            if ($totals['base_debit_total'] <= 0 && $totals['base_credit_total'] <= 0) {
                return Result::failure(new Error(
                    FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY,
                    'Journal entry totals cannot be zero.',
                ));
            }

            if (round($totals['base_debit_total'], 4) !== round($totals['base_credit_total'], 4)) {
                return Result::failure(new Error(
                    FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY,
                    'Journal entry is not balanced in base currency.',
                    $totals,
                ));
            }

            $tenantId = (int) $entry->get('tenant_id');
            $organizationUnitId = $entry->get('organization_unit_id') !== null
                ? (int) $entry->get('organization_unit_id')
                : null;
            $postingDate = isset($payload['posting_date'])
                ? (string) $payload['posting_date']
                : (string) $entry->get('posting_date', $entry->get('entry_date'));

            $openPeriodResult = $this->fiscalPeriodService->requireOpenPeriod(
                $tenantId,
                $postingDate,
                $organizationUnitId,
            );

            if ($openPeriodResult->isFailure()) {
                return Result::failure(new Error(
                    FinanceErrorCode::FISCAL_PERIOD_NOT_OPEN,
                    'Posting date does not belong to an open fiscal period.',
                ));
            }

            $fiscalPeriod = $openPeriodResult->valueOrFail();

            return Result::success([
                'journal_entry_id' => (int) $entry->id(),
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'current_status' => $currentStatus,
                'target_status' => JournalEntryStatus::POSTED,
                'current_row_version' => $currentRowVersion,
                'posting_date' => $postingDate,
                'line_count' => count($lines),
                'totals' => $totals,
                'fiscal_period' => $fiscalPeriod->toArray(),
                'can_post' => true,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param list<DataRecord> $lines
     * @return array<string, float>
     */
    private function calculateTotals(array $lines): array
    {
        $debit = 0.0;
        $credit = 0.0;
        $baseDebit = 0.0;
        $baseCredit = 0.0;

        foreach ($lines as $line) {
            if (! $line instanceof DataRecord) {
                continue;
            }

            $debit += (float) $line->get('debit_amount', 0);
            $credit += (float) $line->get('credit_amount', 0);
            $baseDebit += (float) $line->get('base_debit_amount', 0);
            $baseCredit += (float) $line->get('base_credit_amount', 0);
        }

        return [
            'debit_total' => round($debit, 4),
            'credit_total' => round($credit, 4),
            'base_debit_total' => round($baseDebit, 4),
            'base_credit_total' => round($baseCredit, 4),
        ];
    }
}
