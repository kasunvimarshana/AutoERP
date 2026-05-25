<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\UseCases\PriceLists;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Throwable;

final class CreatePriceListService implements CreatePriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $repository)
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
            return Result::failure(new Error(PricingErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}