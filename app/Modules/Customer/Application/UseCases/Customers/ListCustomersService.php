<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\Customers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\Customers\ListCustomersServiceInterface;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerDefaults;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class ListCustomersService implements ListCustomersServiceInterface
{
    public function __construct(private readonly CustomerRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : CustomerDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('customer.pagination.max_per_page', CustomerDefaults::MAX_PER_PAGE))
                : (int) config('customer.pagination.default_per_page', CustomerDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}