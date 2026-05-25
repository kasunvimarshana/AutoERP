<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\FiscalPeriods;

use Modules\Finance\Application\Contracts\UseCases\FiscalPeriods\UpdateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Repositories\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class UpdateFiscalPeriodService implements UpdateFiscalPeriodServiceInterface
{
    public function __construct(private readonly FiscalPeriodRepositoryInterface $repository)
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
