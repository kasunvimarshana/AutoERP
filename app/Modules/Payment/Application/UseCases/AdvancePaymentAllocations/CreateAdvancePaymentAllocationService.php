<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\AdvancePaymentAllocations;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations\CreateAdvancePaymentAllocationServiceInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class CreateAdvancePaymentAllocationService implements CreateAdvancePaymentAllocationServiceInterface
{
    public function __construct(private readonly AdvancePaymentAllocationServiceInterface $advancePaymentAllocationService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->advancePaymentAllocationService->createAllocation($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
