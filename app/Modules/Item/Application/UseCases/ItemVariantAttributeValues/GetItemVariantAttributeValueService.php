<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemVariantAttributeValues;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\GetItemVariantAttributeValueServiceInterface;
use Modules\Item\Application\Repositories\ItemVariantAttributeValueRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class GetItemVariantAttributeValueService implements GetItemVariantAttributeValueServiceInterface
{
    public function __construct(private readonly ItemVariantAttributeValueRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'ItemVariantAttributeValue not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
