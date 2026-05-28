<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEntries;

use Modules\Finance\Application\Contracts\UseCases\JournalEntries\DeleteJournalEntryServiceInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteJournalEntryService implements DeleteJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $entry = $this->repository->findById($id);
            if ($entry === null) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Journal entry not found.'));
            }

            if (strtoupper((string) $entry->get('status', JournalEntryStatus::DRAFT)) !== JournalEntryStatus::DRAFT) {
                return Result::failure(new Error(
                    FinanceErrorCode::IMMUTABLE_POSTED_JOURNAL,
                    'Only draft journal entries can be deleted.',
                ));
            }

            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'JournalEntry not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
