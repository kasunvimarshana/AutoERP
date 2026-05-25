<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\BankTransactions;

use Modules\Finance\Application\Contracts\UseCases\BankTransactions\DeleteBankTransactionServiceInterface;
use Modules\Finance\Application\Repositories\BankTransactionRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteBankTransactionService implements DeleteBankTransactionServiceInterface
{
    public function __construct(private readonly BankTransactionRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'BankTransaction not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
