<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\PaymentMethods;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\GetPaymentMethodServiceInterface;
use Modules\Payment\Application\Repositories\PaymentMethodRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class GetPaymentMethodService implements GetPaymentMethodServiceInterface
{
    public function __construct(private readonly PaymentMethodRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'PaymentMethod not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}