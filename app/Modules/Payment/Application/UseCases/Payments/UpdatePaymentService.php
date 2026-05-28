<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\Payments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\PaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\UpdatePaymentServiceInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class UpdatePaymentService implements UpdatePaymentServiceInterface
{
    public function __construct(private readonly PaymentServiceInterface $paymentService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->paymentService->updatePayment($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
