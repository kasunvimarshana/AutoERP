<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\FiscalPeriods;

use Modules\Finance\Application\Contracts\UseCases\FiscalPeriods\DeleteFiscalPeriodServiceInterface;
use Modules\Finance\Application\Repositories\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteFiscalPeriodService implements DeleteFiscalPeriodServiceInterface
{
    public function __construct(private readonly FiscalPeriodRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'FiscalPeriod not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
