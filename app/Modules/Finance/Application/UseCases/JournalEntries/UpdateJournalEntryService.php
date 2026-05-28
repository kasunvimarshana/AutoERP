<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEntries;

use Modules\Finance\Application\Contracts\UseCases\JournalEntries\UpdateJournalEntryServiceInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Domain\Constants\JournalEntryStatus;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class UpdateJournalEntryService implements UpdateJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $repository)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->repository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Journal entry not found.'));
            }

            if (
                strtoupper((string) $existing->get('status', JournalEntryStatus::DRAFT))
                !== JournalEntryStatus::DRAFT
            ) {
                return Result::failure(new Error(
                    FinanceErrorCode::IMMUTABLE_POSTED_JOURNAL,
                    'Only draft journal entries can be edited.',
                ));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
