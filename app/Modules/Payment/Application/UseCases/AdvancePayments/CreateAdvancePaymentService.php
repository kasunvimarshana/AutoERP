<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\AdvancePayments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\CreateAdvancePaymentServiceInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class CreateAdvancePaymentService implements CreateAdvancePaymentServiceInterface
{
    public function __construct(private readonly AdvancePaymentServiceInterface $advancePaymentService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->advancePaymentService->createAdvance($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
