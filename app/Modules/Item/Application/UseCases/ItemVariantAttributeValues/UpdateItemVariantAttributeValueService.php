<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemVariantAttributeValues;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\UpdateItemVariantAttributeValueServiceInterface;
use Modules\Item\Application\Repositories\ItemVariantAttributeValueRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class UpdateItemVariantAttributeValueService implements UpdateItemVariantAttributeValueServiceInterface
{
    public function __construct(private readonly ItemVariantAttributeValueRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'ItemVariantAttributeValue not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
