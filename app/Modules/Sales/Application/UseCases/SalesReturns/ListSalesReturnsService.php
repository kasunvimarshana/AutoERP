<?php

declare(strict_types=1);

namespace Modules\Sales\Application\UseCases\SalesReturns;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\UseCases\SalesReturns\ListSalesReturnsServiceInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesDefaults;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Throwable;

final class ListSalesReturnsService implements ListSalesReturnsServiceInterface
{
    public function __construct(private readonly SalesReturnRepositoryInterface $repository) {}

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : SalesDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('sales.pagination.max_per_page', SalesDefaults::MAX_PER_PAGE))
                : (int) config('sales.pagination.default_per_page', SalesDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
