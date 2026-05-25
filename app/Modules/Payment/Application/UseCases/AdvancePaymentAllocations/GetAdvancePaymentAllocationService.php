<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\AdvancePaymentAllocations;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\AdvancePaymentAllocations\GetAdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentAllocationRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class GetAdvancePaymentAllocationService implements GetAdvancePaymentAllocationServiceInterface
{
    public function __construct(private readonly AdvancePaymentAllocationRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'AdvancePaymentAllocation not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}