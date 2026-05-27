<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\FinanceCore;

use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\GenerateJournalEntryFromEventServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\PostJournalToLedgerServiceInterface;
use Modules\Finance\Application\DTOs\FinancePostingEventData;
use Modules\Finance\Application\Repositories\FinanceProcessedEventRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Throwable;

final class GenerateJournalEntryFromEventService implements GenerateJournalEntryFromEventServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntries,
        private readonly JournalEntryLineRepositoryInterface $journalEntryLines,
        private readonly FinanceProcessedEventRepositoryInterface $processedEvents,
        private readonly PostJournalToLedgerServiceInterface $postJournalToLedger,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {
    }

    public function execute(array $payload): Result
    {
        $eventData = FinancePostingEventData::fromArray($payload);

        if ($eventData->tenantId < 1) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, 'Tenant id is required.'));
        }

        if ($eventData->eventType === '' || $eventData->idempotencyKey === '') {
            return Result::failure(new Error(
                FinanceErrorCode::INVALID_VALUE,
                'Event type and idempotency key are required.',
            ));
        }

        if (
            $this->processedEvents->exists([
                'tenant_id' => $eventData->tenantId,
                'event_type' => $eventData->eventType,
                'idempotency_key' => $eventData->idempotencyKey,
            ])
        ) {
            return Result::failure(new Error(
                FinanceErrorCode::EVENT_ALREADY_PROCESSED,
                'Event already processed.',
                [
                    'event_type' => $eventData->eventType,
                    'idempotency_key' => $eventData->idempotencyKey,
                ],
            ));
        }

        if ($eventData->entryNumber === '' || $eventData->entryDate === '') {
            return Result::failure(new Error(
                FinanceErrorCode::INVALID_VALUE,
                'Entry number and entry date are required.',
            ));
        }

        if ($eventData->lines === []) {
            return Result::failure(new Error(
                FinanceErrorCode::INVALID_VALUE,
                'At least one journal line is required.',
            ));
        }

        $totals = $this->calculateTotals($eventData);
        if ($totals['debit'] <= 0 || $totals['credit'] <= 0 || $totals['debit'] !== $totals['credit']) {
            return Result::failure(new Error(
                FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY,
                'Journal entry lines are not balanced.',
                $totals,
            ));
        }

        try {
            $result = $this->transactionManager->runInTransaction(function () use ($eventData): array {
                $entry = $this->journalEntries->create([
                    'tenant_id' => $eventData->tenantId,
                    'organization_unit_id' => $eventData->organizationUnitId,
                    'metadata' => $eventData->metadata,
                    'fiscal_period_id' => $eventData->fiscalPeriodId,
                    'entry_number' => $eventData->entryNumber,
                    'entry_type' => 'AUTO',
                    'reference_type' => $eventData->referenceType,
                    'reference_id' => $eventData->referenceId,
                    'description' => $eventData->description,
                    'entry_date' => $eventData->entryDate,
                    'posting_date' => $eventData->entryDate,
                    'status' => JournalEntryStatus::POSTED,
                    'is_reversed' => false,
                    'created_by' => $eventData->actorUserId,
                    'posted_by' => $eventData->actorUserId,
                    'posted_at' => now(),
                    'row_version' => 1,
                ]);

                $lineNumber = 1;
                foreach ($eventData->lines as $line) {
                    $this->journalEntryLines->create([
                        'tenant_id' => $eventData->tenantId,
                        'organization_unit_id' => $eventData->organizationUnitId,
                        'metadata' => is_array($line['metadata'] ?? null) ? $line['metadata'] : [],
                        'journal_entry_id' => (int) $entry->id(),
                        'account_id' => (int) ($line['account_id'] ?? 0),
                        'description' => isset($line['description']) ? (string) $line['description'] : null,
                        'debit_amount' => (float) ($line['debit_amount'] ?? 0),
                        'credit_amount' => (float) ($line['credit_amount'] ?? 0),
                        'currency_id' => $line['currency_id'] ?? null,
                        'exchange_rate' => (float) ($line['exchange_rate'] ?? 1),
                        'base_debit_amount' => (float) ($line['base_debit_amount'] ?? $line['debit_amount'] ?? 0),
                        'base_credit_amount' => (float) ($line['base_credit_amount'] ?? $line['credit_amount'] ?? 0),
                        'cost_center_id' => $line['cost_center_id'] ?? null,
                        'tax_rate_id' => $line['tax_rate_id'] ?? null,
                        'tax_amount' => (float) ($line['tax_amount'] ?? 0),
                        'line_number' => $lineNumber,
                        'row_version' => 1,
                    ]);
                    $lineNumber++;
                }

                $ledgerPost = $this->postJournalToLedger->execute((int) $entry->id());
                if ($ledgerPost->isFailure()) {
                    throw new \RuntimeException($ledgerPost->errorOrFail()->code);
                }

                $processedEvent = $this->processedEvents->create([
                    'tenant_id' => $eventData->tenantId,
                    'organization_unit_id' => $eventData->organizationUnitId,
                    'metadata' => $eventData->metadata,
                    'event_type' => $eventData->eventType,
                    'idempotency_key' => $eventData->idempotencyKey,
                    'source_module' => $eventData->sourceModule,
                    'journal_entry_id' => (int) $entry->id(),
                    'actor_user_id' => $eventData->actorUserId,
                    'payload' => [
                        'event' => $eventData->eventType,
                        'entry_number' => $eventData->entryNumber,
                        'lines' => $eventData->lines,
                    ],
                    'processed_at' => now(),
                    'row_version' => 1,
                ]);

                return [
                    'journal_entry' => $entry->toArray(),
                    'processed_event_id' => (int) $processedEvent->id(),
                    'ledger' => $ledgerPost->valueOrFail(),
                ];
            });

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                FinanceErrorCode::INVALID_VALUE,
                ['event_type' => $eventData->eventType],
            ));
        }
    }

    /**
     * @return array{debit: float, credit: float}
     */
    private function calculateTotals(FinancePostingEventData $eventData): array
    {
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($eventData->lines as $line) {
            $debitTotal += (float) ($line['base_debit_amount'] ?? $line['debit_amount'] ?? 0);
            $creditTotal += (float) ($line['base_credit_amount'] ?? $line['credit_amount'] ?? 0);
        }

        return [
            'debit' => round($debitTotal, 4),
            'credit' => round($creditTotal, 4),
        ];
    }
}
