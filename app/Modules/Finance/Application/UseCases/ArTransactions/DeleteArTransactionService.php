<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\ArTransactions;

use Modules\Finance\Application\Contracts\UseCases\ArTransactions\DeleteArTransactionServiceInterface;
use Modules\Finance\Application\Repositories\ArTransactionRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteArTransactionService implements DeleteArTransactionServiceInterface
{
    public function __construct(private readonly ArTransactionRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'ArTransaction not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
