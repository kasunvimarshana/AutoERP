<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\UseCases\Vouchers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Voucher\Application\Contracts\UseCases\Vouchers\ListVouchersServiceInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;
use Modules\Voucher\Domain\Constants\VoucherDefaults;
use Modules\Voucher\Domain\Constants\VoucherErrorCode;
use Throwable;

final class ListVouchersService implements ListVouchersServiceInterface
{
    public function __construct(private readonly VoucherRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : VoucherDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('voucher.pagination.max_per_page', VoucherDefaults::MAX_PER_PAGE))
                : (int) config('voucher.pagination.default_per_page', VoucherDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VoucherErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}