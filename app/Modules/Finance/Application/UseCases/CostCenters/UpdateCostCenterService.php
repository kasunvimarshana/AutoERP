<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\CostCenters;

use Modules\Finance\Application\Contracts\UseCases\CostCenters\UpdateCostCenterServiceInterface;
use Modules\Finance\Application\Repositories\CostCenterRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class UpdateCostCenterService implements UpdateCostCenterServiceInterface
{
    public function __construct(private readonly CostCenterRepositoryInterface $repository)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result
    {
        try {
            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
