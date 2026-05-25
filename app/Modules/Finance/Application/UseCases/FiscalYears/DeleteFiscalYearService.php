<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\FiscalYears;

use Modules\Finance\Application\Contracts\UseCases\FiscalYears\DeleteFiscalYearServiceInterface;
use Modules\Finance\Application\Repositories\FiscalYearRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteFiscalYearService implements DeleteFiscalYearServiceInterface
{
    public function __construct(private readonly FiscalYearRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'FiscalYear not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
