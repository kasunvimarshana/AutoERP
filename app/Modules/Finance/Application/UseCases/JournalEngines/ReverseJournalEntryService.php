<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\PostJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\ReverseJournalEntryServiceInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Throwable;

final class ReverseJournalEntryService implements ReverseJournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly JournalEntryLineRepositoryInterface $lines,
        private readonly PostJournalEntryServiceInterface $postService,
    ) {}

    public function execute(int|string $journalEntryId, array $payload): Result
    {
        try {
            $entry = $this->entries->findById($journalEntryId);
            if (! $entry instanceof DataRecord) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Journal entry not found.'));
            }

            $currentStatus = strtoupper(trim((string) $entry->get('status', JournalEntryStatus::DRAFT)));
            if (! JournalEntryStatus::canTransition($currentStatus, JournalEntryStatus::REVERSED)) {
                return Result::failure(new Error(
                    FinanceErrorCode::INVALID_STATUS_TRANSITION,
                    'Journal entry cannot be reversed from current status.',
                    ['current_status' => $currentStatus],
                ));
            }

            if ((bool) $entry->get('is_reversed', false)) {
                return Result::failure(new Error(
                    FinanceErrorCode::INVALID_VALUE,
                    'Journal entry already marked as reversed.',
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

            $result = $this->entries->transaction(function () use ($entry, $payload, $currentRowVersion): array {
                $sourceLines = $this->lines->listByJournalEntry((int) $entry->id());
                if ($sourceLines === []) {
                    throw new \RuntimeException(FinanceErrorCode::INVALID_VALUE);
                }

                $reversalEntry = $this->entries->create([
                    'tenant_id' => (int) $entry->get('tenant_id'),
                    'organization_unit_id' => $entry->get('organization_unit_id'),
                    'metadata' => $entry->get('metadata'),
                    'fiscal_period_id' => $entry->get('fiscal_period_id'),
                    'entry_number' => $this->generateReversalNumber((string) $entry->get('entry_number')),
                    'entry_type' => 'ADJUSTMENT',
                    'reference_type' => 'JOURNAL_REVERSAL',
                    'reference_id' => (int) $entry->id(),
                    'description' => 'Reversal for entry #'.$entry->get('entry_number'),
                    'entry_date' => $payload['posting_date'] ?? now()->toDateString(),
                    'status' => JournalEntryStatus::DRAFT,
                    'created_by' => $payload['posted_by'] ?? $entry->get('created_by'),
                ]);

                $lineNumber = 1;
                foreach ($sourceLines as $line) {
                    $this->lines->create([
                        'tenant_id' => (int) $entry->get('tenant_id'),
                        'organization_unit_id' => $line->get('organization_unit_id'),
                        'metadata' => $line->get('metadata'),
                        'journal_entry_id' => (int) $reversalEntry->id(),
                        'account_id' => (int) $line->get('account_id'),
                        'description' => $line->get('description'),
                        'debit_amount' => (float) $line->get('credit_amount', 0),
                        'credit_amount' => (float) $line->get('debit_amount', 0),
                        'currency_id' => $line->get('currency_id'),
                        'exchange_rate' => (float) $line->get('exchange_rate', 1),
                        'base_debit_amount' => (float) $line->get('base_credit_amount', 0),
                        'base_credit_amount' => (float) $line->get('base_debit_amount', 0),
                        'cost_center_id' => $line->get('cost_center_id'),
                        'party_type' => $line->get('party_type'),
                        'party_id' => $line->get('party_id'),
                        'tax_rate_id' => $line->get('tax_rate_id'),
                        'tax_amount' => (float) $line->get('tax_amount', 0),
                        'line_number' => $lineNumber++,
                        'source_line_reference' => $line->get('source_line_reference'),
                    ]);
                }

                $postResult = $this->postService->execute((int) $reversalEntry->id(), [
                    'posting_date' => $payload['posting_date'] ?? now()->toDateString(),
                    'posted_by' => $payload['posted_by'] ?? null,
                ]);
                if ($postResult->isFailure()) {
                    throw new \RuntimeException($postResult->errorOrFail()->code);
                }

                $updatedOriginal = $this->entries->update((int) $entry->id(), [
                    'status' => JournalEntryStatus::REVERSED,
                    'is_reversed' => true,
                    'reversal_entry_id' => (int) $reversalEntry->id(),
                    'reversed_at' => now(),
                    'row_version' => $currentRowVersion + 1,
                ]);

                return [
                    'original' => $updatedOriginal->toArray(),
                    'reversal_entry_id' => (int) $reversalEntry->id(),
                ];
            });

            return Result::success($result);
        } catch (Throwable $exception) {
            $known = [
                FinanceErrorCode::INVALID_VALUE,
                FinanceErrorCode::FISCAL_PERIOD_NOT_OPEN,
                FinanceErrorCode::ACCOUNT_NOT_POSTABLE,
                FinanceErrorCode::INVALID_JOURNAL_LINE,
                FinanceErrorCode::UNBALANCED_JOURNAL_ENTRY,
            ];

            $code = in_array($exception->getMessage(), $known, true)
                ? $exception->getMessage()
                : FinanceErrorCode::INVALID_VALUE;

            return Result::failure(new Error($code, $exception->getMessage()));
        }
    }

    private function generateReversalNumber(string $entryNumber): string
    {
        return $entryNumber.'-REV-'.now()->format('YmdHisv');
    }
}
