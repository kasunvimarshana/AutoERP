<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\Payments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\Payments\ListPaymentsServiceInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentDefaults;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class ListPaymentsService implements ListPaymentsServiceInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : PaymentDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('payment.pagination.max_per_page', PaymentDefaults::MAX_PER_PAGE))
                : (int) config('payment.pagination.default_per_page', PaymentDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}