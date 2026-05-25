<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\UseCases\PriceListItems;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\ListPriceListItemsServiceInterface;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\Constants\PricingDefaults;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Throwable;

final class ListPriceListItemsService implements ListPriceListItemsServiceInterface
{
    public function __construct(private readonly PriceListItemRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : PricingDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('pricing.pagination.max_per_page', PricingDefaults::MAX_PER_PAGE))
                : (int) config('pricing.pagination.default_per_page', PricingDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}