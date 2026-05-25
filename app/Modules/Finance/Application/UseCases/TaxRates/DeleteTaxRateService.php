<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\TaxRates;

use Modules\Finance\Application\Contracts\UseCases\TaxRates\DeleteTaxRateServiceInterface;
use Modules\Finance\Application\Repositories\TaxRateRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteTaxRateService implements DeleteTaxRateServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'TaxRate not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
