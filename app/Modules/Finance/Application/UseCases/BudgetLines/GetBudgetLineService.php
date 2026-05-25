<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\BudgetLines;

use Modules\Finance\Application\Contracts\UseCases\BudgetLines\GetBudgetLineServiceInterface;
use Modules\Finance\Application\Repositories\BudgetLineRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetBudgetLineService implements GetBudgetLineServiceInterface
{
    public function __construct(private readonly BudgetLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);
            if ($record === null) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'BudgetLine not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
