<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\UseCases\Vouchers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\DeleteVoucherServiceInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;
use Modules\Voucher\Domain\Constants\VoucherErrorCode;
use Throwable;

final class DeleteVoucherService implements DeleteVoucherServiceInterface
{
    public function __construct(private readonly VoucherRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(VoucherErrorCode::NOT_FOUND, 'Voucher not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VoucherErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}