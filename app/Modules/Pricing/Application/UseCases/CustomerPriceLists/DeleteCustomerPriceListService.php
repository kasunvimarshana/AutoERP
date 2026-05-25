<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\UseCases\CustomerPriceLists;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\DeleteCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Repositories\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Throwable;

final class DeleteCustomerPriceListService implements DeleteCustomerPriceListServiceInterface
{
    public function __construct(private readonly CustomerPriceListRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(PricingErrorCode::NOT_FOUND, 'CustomerPriceList not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}