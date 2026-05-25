<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\PaymentTerms;

use Modules\Finance\Application\Contracts\UseCases\PaymentTerms\DeletePaymentTermServiceInterface;
use Modules\Finance\Application\Repositories\PaymentTermRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeletePaymentTermService implements DeletePaymentTermServiceInterface
{
    public function __construct(private readonly PaymentTermRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'PaymentTerm not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
