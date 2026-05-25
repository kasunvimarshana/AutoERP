<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\UseCases\PriceListItems;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\Constants\PricingErrorCode;
use Throwable;

final class CreatePriceListItemService implements CreatePriceListItemServiceInterface
{
    public function __construct(private readonly PriceListItemRepositoryInterface $repository)
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