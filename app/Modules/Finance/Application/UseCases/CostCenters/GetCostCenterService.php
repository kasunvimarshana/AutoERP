<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\CostCenters;

use Modules\Finance\Application\Contracts\UseCases\CostCenters\GetCostCenterServiceInterface;
use Modules\Finance\Application\Repositories\CostCenterRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetCostCenterService implements GetCostCenterServiceInterface
{
    public function __construct(private readonly CostCenterRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);
            if ($record === null) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'CostCenter not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
