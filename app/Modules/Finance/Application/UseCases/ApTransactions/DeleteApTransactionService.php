<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\ApTransactions;

use Modules\Finance\Application\Contracts\UseCases\ApTransactions\DeleteApTransactionServiceInterface;
use Modules\Finance\Application\Repositories\ApTransactionRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteApTransactionService implements DeleteApTransactionServiceInterface
{
    public function __construct(private readonly ApTransactionRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'ApTransaction not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
