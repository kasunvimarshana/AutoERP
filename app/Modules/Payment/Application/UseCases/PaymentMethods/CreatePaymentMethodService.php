<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\PaymentMethods;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\PaymentMethods\CreatePaymentMethodServiceInterface;
use Modules\Payment\Application\Repositories\PaymentMethodRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class CreatePaymentMethodService implements CreatePaymentMethodServiceInterface
{
    public function __construct(private readonly PaymentMethodRepositoryInterface $repository)
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