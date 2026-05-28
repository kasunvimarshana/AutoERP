<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\PaymentAllocations;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\PaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\CreatePaymentAllocationServiceInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class CreatePaymentAllocationService implements CreatePaymentAllocationServiceInterface
{
    public function __construct(private readonly PaymentAllocationServiceInterface $paymentAllocationService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->paymentAllocationService->createAllocation($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
