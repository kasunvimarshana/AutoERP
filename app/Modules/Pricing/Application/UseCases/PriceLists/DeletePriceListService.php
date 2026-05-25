<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\UseCases\PriceLists;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Throwable;

final class DeletePriceListService implements DeletePriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(PricingErrorCode::NOT_FOUND, 'PriceList not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}