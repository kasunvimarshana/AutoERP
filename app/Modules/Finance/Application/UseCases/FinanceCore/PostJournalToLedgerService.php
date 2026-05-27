<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\FinanceCore;

use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\PostJournalToLedgerServiceInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Application\Repositories\LedgerEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Throwable;

final class PostJournalToLedgerService implements PostJournalToLedgerServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntries,
        private readonly JournalEntryLineRepositoryInterface $journalEntryLines,
        private readonly LedgerEntryRepositoryInterface $ledgerEntries,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {
    }

    public function execute(int|string $journalEntryId, array $payload = []): Result
    {
        try {
            $entry = $this->journalEntries->findById($journalEntryId);
            if (! $entry instanceof DataRecord) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Journal entry not found.'));
            }

            $status = strtoupper(trim((string) $entry->get('status', JournalEntryStatus::DRAFT)));
            if ($status !== JournalEntryStatus::POSTED) {
                return Result::failure(new Error(
                    FinanceErrorCode::INVALID_STATUS_TRANSITION,
                    'Only posted journal entries can be moved to ledger.',
                    ['status' => $status],
                ));
            }

            $tenantId = (int) $entry->get('tenant_id', 0);
            $entryId = (int) $entry->id();
            if ($this->ledgerEntries->exists(['tenant_id' => $tenantId, 'journal_entry_id' => $entryId])) {
                return Result::failure(new Error(
                    FinanceErrorCode::LEDGER_ALREADY_POSTED,
                    'Ledger entries already exist for this journal entry.',
                ));
            }

            $lines = $this->journalEntryLines->list(['journal_entry_id' => $entryId]);
            if ($lines === []) {
                return Result::failure(new Error(
                    FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY,
                    'Journal entry has no lines to post.',
                ));
            }

            $postedCount = $this->transactionManager->runInTransaction(function () use ($entry, $lines): int {
                $count = 0;
                foreach ($lines as $line) {
                    if (! $line instanceof DataRecord) {
                        continue;
                    }

                    $count += $this->createLedgerRowsForLine($entry, $line);
                }

                return $count;
            });

            return Result::success([
                'journal_entry_id' => $entryId,
                'ledger_entry_count' => (int) $postedCount,
            ]);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                FinanceErrorCode::INVALID_VALUE,
                ['journal_entry_id' => $journalEntryId],
            ));
        }
    }

    private function createLedgerRowsForLine(DataRecord $entry, DataRecord $line): int
    {
        $createdCount = 0;

        $debitAmount = $this->resolvePositiveAmount($line, 'base_debit_amount', 'debit_amount');
        if ($debitAmount > 0) {
            $this->ledgerEntries->create($this->buildLedgerAttributes($entry, $line, 'DEBIT', $debitAmount));
            $createdCount++;
        }

        $creditAmount = $this->resolvePositiveAmount($line, 'base_credit_amount', 'credit_amount');
        if ($creditAmount > 0) {
            $this->ledgerEntries->create($this->buildLedgerAttributes($entry, $line, 'CREDIT', $creditAmount));
            $createdCount++;
        }

        return $createdCount;
    }

    private function resolvePositiveAmount(DataRecord $line, string $baseKey, string $fallbackKey): float
    {
        $base = (float) $line->get($baseKey, 0);
        if ($base > 0) {
            return round($base, 4);
        }

        $fallback = (float) $line->get($fallbackKey, 0);

        return round(max($fallback, 0), 4);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLedgerAttributes(
        DataRecord $entry,
        DataRecord $line,
        string $entryType,
        float $amount,
    ): array {
        $accountId = (int) $line->get('account_id', 0);
        $tenantId = (int) $entry->get('tenant_id', 0);
        $previousBalance = $this->resolveCurrentBalance($tenantId, $accountId);
        $signedAmount = $entryType === 'CREDIT' ? (-1 * $amount) : $amount;

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $entry->get('organization_unit_id'),
            'metadata' => [
                'source' => 'finance.core.post_journal_to_ledger',
            ],
            'journal_entry_id' => (int) $entry->id(),
            'journal_entry_line_id' => (int) $line->id(),
            'account_id' => $accountId,
            'posting_date' => (string) $entry->get('posting_date', $entry->get('entry_date')),
            'entry_type' => $entryType,
            'amount' => $amount,
            'running_balance' => round($previousBalance + $signedAmount, 4),
            'currency_id' => $line->get('currency_id'),
            'created_by' => $entry->get('posted_by', $entry->get('created_by')),
            'row_version' => 1,
        ];
    }

    private function resolveCurrentBalance(int $tenantId, int $accountId): float
    {
        $entries = $this->ledgerEntries->list([
            'tenant_id' => $tenantId,
            'account_id' => $accountId,
        ]);

        if ($entries === []) {
            return 0.0;
        }

        usort(
            $entries,
            static fn (DataRecord $left, DataRecord $right): int => (int) $left->id() <=> (int) $right->id(),
        );

        $latest = $entries[count($entries) - 1];

        return (float) $latest->get('running_balance', 0);
    }
}
