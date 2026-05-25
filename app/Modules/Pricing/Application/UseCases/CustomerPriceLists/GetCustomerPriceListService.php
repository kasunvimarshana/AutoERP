<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\UseCases\CustomerPriceLists;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\GetCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Repositories\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Throwable;

final class GetCustomerPriceListService implements GetCustomerPriceListServiceInterface
{
    public function __construct(private readonly CustomerPriceListRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(PricingErrorCode::NOT_FOUND, 'CustomerPriceList not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}