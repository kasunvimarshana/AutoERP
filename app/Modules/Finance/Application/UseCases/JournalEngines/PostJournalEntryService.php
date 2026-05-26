<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\PostJournalEntryServiceInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Throwable;

final class PostJournalEntryService implements PostJournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly JournalEntryLineRepositoryInterface $lines,
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

            $result = $this->entries->transaction(function () use ($entry, $payload): array {
                $lines = $this->lines->list(['journal_entry_id' => (int) $entry->id()]);
                if ($lines === []) {
                    throw new \RuntimeException(FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY);
                }

                $totals = $this->calculateTotals($lines);
                if ($totals['base_debit_total'] <= 0 && $totals['base_credit_total'] <= 0) {
                    throw new \RuntimeException(FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY);
                }

                if (round($totals['base_debit_total'], 4) !== round($totals['base_credit_total'], 4)) {
                    throw new \RuntimeException(FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY);
                }

                $postedBy = isset($payload['posted_by']) ? (int) $payload['posted_by'] : null;
                $postingDate = isset($payload['posting_date'])
                    ? (string) $payload['posting_date']
                    : (string) $entry->get('posting_date', $entry->get('entry_date'));

                $updated = $this->entries->update((int) $entry->id(), [
                    'status' => JournalEntryStatus::POSTED,
                    'posting_date' => $postingDate,
                    'posted_by' => $postedBy,
                    'posted_at' => now(),
                    'row_version' => ((int) $entry->get('row_version', 1)) + 1,
                ]);

                return [
                    'journal_entry' => $updated->toArray(),
                    'totals' => $totals,
                    'line_count' => count($lines),
                ];
            });

            return Result::success($result);
        } catch (Throwable $exception) {
            $code = $exception->getMessage() === FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY
                ? FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY
                : FinanceErrorCode::INVALID_VALUE;

            return Result::failure(new Error($code, $exception->getMessage()));
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
