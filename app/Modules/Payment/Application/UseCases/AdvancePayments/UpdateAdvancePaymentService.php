<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\AdvancePayments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\UpdateAdvancePaymentServiceInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class UpdateAdvancePaymentService implements UpdateAdvancePaymentServiceInterface
{
    public function __construct(private readonly AdvancePaymentRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'AdvancePayment not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}