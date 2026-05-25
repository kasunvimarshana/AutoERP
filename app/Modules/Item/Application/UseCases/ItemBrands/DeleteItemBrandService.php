<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemBrands;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\DeleteItemBrandServiceInterface;
use Modules\Item\Application\Repositories\ItemBrandRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class DeleteItemBrandService implements DeleteItemBrandServiceInterface
{
    public function __construct(private readonly ItemBrandRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'ItemBrand not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
