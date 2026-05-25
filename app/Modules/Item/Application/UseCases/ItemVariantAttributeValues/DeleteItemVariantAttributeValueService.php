<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemVariantAttributeValues;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\DeleteItemVariantAttributeValueServiceInterface;
use Modules\Item\Application\Repositories\ItemVariantAttributeValueRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class DeleteItemVariantAttributeValueService implements DeleteItemVariantAttributeValueServiceInterface
{
    public function __construct(private readonly ItemVariantAttributeValueRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'ItemVariantAttributeValue not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
