<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\BankTransactions;

use Modules\Finance\Application\Contracts\UseCases\BankTransactions\CreateBankTransactionServiceInterface;
use Modules\Finance\Application\Repositories\BankTransactionRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class CreateBankTransactionService implements CreateBankTransactionServiceInterface
{
    public function __construct(private readonly BankTransactionRepositoryInterface $repository)
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
