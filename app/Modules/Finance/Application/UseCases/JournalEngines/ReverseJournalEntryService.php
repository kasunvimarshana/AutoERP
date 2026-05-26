<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\ReverseJournalEntryServiceInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Throwable;

final class ReverseJournalEntryService implements ReverseJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $entries)
    {
    }

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

            $updated = $this->entries->update((int) $entry->id(), [
                'status' => JournalEntryStatus::REVERSED,
                'is_reversed' => true,
                'reversal_entry_id' => isset($payload['reversal_entry_id'])
                    ? (int) $payload['reversal_entry_id']
                    : $entry->get('reversal_entry_id'),
                'row_version' => $currentRowVersion + 1,
            ]);

            return Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
