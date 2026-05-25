<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEntries;

use Modules\Finance\Application\Contracts\UseCases\JournalEntries\CreateJournalEntryServiceInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class CreateJournalEntryService implements CreateJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $repository)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result
    {
        try {
            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
