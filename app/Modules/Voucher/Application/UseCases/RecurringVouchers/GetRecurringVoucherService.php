<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\UseCases\RecurringVouchers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\UseCases\RecurringVouchers\GetRecurringVoucherServiceInterface;
use Modules\Voucher\Application\Repositories\RecurringVoucherRepositoryInterface;
use Modules\Voucher\Domain\Constants\VoucherErrorCode;
use Throwable;

final class GetRecurringVoucherService implements GetRecurringVoucherServiceInterface
{
    public function __construct(private readonly RecurringVoucherRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(VoucherErrorCode::NOT_FOUND, 'RecurringVoucher not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VoucherErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}