<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\JournalEntryLines;

use Modules\Finance\Application\Contracts\UseCases\JournalEntryLines\CreateJournalEntryLineServiceInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class CreateJournalEntryLineService implements CreateJournalEntryLineServiceInterface
{
    public function __construct(private readonly JournalEntryLineRepositoryInterface $repository)
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
