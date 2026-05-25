<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\PaymentMethods;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\UpdatePaymentMethodServiceInterface;
use Modules\Payment\Application\Repositories\PaymentMethodRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class UpdatePaymentMethodService implements UpdatePaymentMethodServiceInterface
{
    public function __construct(private readonly PaymentMethodRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'PaymentMethod not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}