<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\CashRegisters;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\CashRegisters\DeleteCashRegisterServiceInterface;
use Modules\Payment\Application\Repositories\CashRegisterRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class DeleteCashRegisterService implements DeleteCashRegisterServiceInterface
{
    public function __construct(private readonly CashRegisterRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'CashRegister not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}