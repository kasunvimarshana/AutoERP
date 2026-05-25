<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases\PurchaseReturns;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns\ListPurchaseReturnsServiceInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnRepositoryInterface;
use Modules\Purchase\Domain\Constants\PurchaseDefaults;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Throwable;

final class ListPurchaseReturnsService implements ListPurchaseReturnsServiceInterface
{
    public function __construct(private readonly PurchaseReturnRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : PurchaseDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('purchase.pagination.max_per_page', PurchaseDefaults::MAX_PER_PAGE))
                : (int) config('purchase.pagination.default_per_page', PurchaseDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}