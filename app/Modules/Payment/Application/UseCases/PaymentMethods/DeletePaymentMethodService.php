<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\PaymentMethods;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\DeletePaymentMethodServiceInterface;
use Modules\Payment\Application\Repositories\PaymentMethodRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class DeletePaymentMethodService implements DeletePaymentMethodServiceInterface
{
    public function __construct(private readonly PaymentMethodRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'PaymentMethod not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}