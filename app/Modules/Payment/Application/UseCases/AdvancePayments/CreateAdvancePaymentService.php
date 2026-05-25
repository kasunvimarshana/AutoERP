<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\AdvancePayments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\CreateAdvancePaymentServiceInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class CreateAdvancePaymentService implements CreateAdvancePaymentServiceInterface
{
    public function __construct(private readonly AdvancePaymentRepositoryInterface $repository)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}