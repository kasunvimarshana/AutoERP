<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\PaymentAllocations;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\PaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\UpdatePaymentAllocationServiceInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class UpdatePaymentAllocationService implements UpdatePaymentAllocationServiceInterface
{
    public function __construct(private readonly PaymentAllocationServiceInterface $paymentAllocationService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->paymentAllocationService->updateAllocation($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
