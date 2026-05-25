<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\UseCases\Vouchers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\CreateVoucherServiceInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;
use Modules\Voucher\Domain\Constants\VoucherErrorCode;
use Throwable;

final class CreateVoucherService implements CreateVoucherServiceInterface
{
    public function __construct(private readonly VoucherRepositoryInterface $repository)
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
            return Result::failure(new Error(VoucherErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}