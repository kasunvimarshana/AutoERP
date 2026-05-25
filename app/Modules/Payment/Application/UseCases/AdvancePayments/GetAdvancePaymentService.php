<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\AdvancePayments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\GetAdvancePaymentServiceInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class GetAdvancePaymentService implements GetAdvancePaymentServiceInterface
{
    public function __construct(private readonly AdvancePaymentRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'AdvancePayment not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}