<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\AdvancePaymentAllocations;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations\UpdateAdvancePaymentAllocationServiceInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class UpdateAdvancePaymentAllocationService implements UpdateAdvancePaymentAllocationServiceInterface
{
    public function __construct(private readonly AdvancePaymentAllocationServiceInterface $advancePaymentAllocationService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->advancePaymentAllocationService->updateAllocation($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
