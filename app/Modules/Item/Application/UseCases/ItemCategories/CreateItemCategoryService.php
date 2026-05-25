<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemCategories;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\CreateItemCategoryServiceInterface;
use Modules\Item\Application\Repositories\ItemCategoryRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class CreateItemCategoryService implements CreateItemCategoryServiceInterface
{
    public function __construct(private readonly ItemCategoryRepositoryInterface $repository)
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
