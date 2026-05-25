<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemCategories;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\GetItemCategoryServiceInterface;
use Modules\Item\Application\Repositories\ItemCategoryRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class GetItemCategoryService implements GetItemCategoryServiceInterface
{
    public function __construct(private readonly ItemCategoryRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'ItemCategory not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
