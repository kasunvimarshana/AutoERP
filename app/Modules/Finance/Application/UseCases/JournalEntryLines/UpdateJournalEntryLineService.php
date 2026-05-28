<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEntryLines;

use Modules\Finance\Application\Contracts\UseCases\JournalEntryLines\UpdateJournalEntryLineServiceInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class UpdateJournalEntryLineService implements UpdateJournalEntryLineServiceInterface
{
    public function __construct(
        private readonly JournalEntryLineRepositoryInterface $repository,
        private readonly JournalEntryRepositoryInterface $entryRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result
    {
        try {
            $line = $this->repository->findById($id);
            if ($line === null) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Journal entry line not found.'));
            }

            $entry = $this->entryRepository->findById((int) $line->get('journal_entry_id'));
            if ($entry === null) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Journal entry not found.'));
            }

            if (strtoupper((string) $entry->get('status', JournalEntryStatus::DRAFT)) !== JournalEntryStatus::DRAFT) {
                return Result::failure(new Error(
                    FinanceErrorCode::IMMUTABLE_POSTED_JOURNAL,
                    'Lines can only be modified in draft journal entries.',
                ));
            }

            $debit = (float) ($payload['debit_amount'] ?? $line->get('debit_amount', 0));
            $credit = (float) ($payload['credit_amount'] ?? $line->get('credit_amount', 0));

            if (($debit <= 0 && $credit <= 0) || ($debit > 0 && $credit > 0)) {
                return Result::failure(new Error(
                    FinanceErrorCode::INVALID_JOURNAL_LINE,
                    'Each line requires either debit or credit amount.',
                ));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
