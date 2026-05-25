<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\PaymentGroups;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\PaymentGroups\DeletePaymentGroupServiceInterface;
use Modules\Payment\Application\Repositories\PaymentGroupRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class DeletePaymentGroupService implements DeletePaymentGroupServiceInterface
{
    public function __construct(private readonly PaymentGroupRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'PaymentGroup not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}