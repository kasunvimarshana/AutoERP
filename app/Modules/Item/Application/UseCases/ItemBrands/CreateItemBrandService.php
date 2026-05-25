<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemBrands;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\CreateItemBrandServiceInterface;
use Modules\Item\Application\Repositories\ItemBrandRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class CreateItemBrandService implements CreateItemBrandServiceInterface
{
    public function __construct(private readonly ItemBrandRepositoryInterface $repository)
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
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
