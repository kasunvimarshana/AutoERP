<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemAttributes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\GetItemAttributeServiceInterface;
use Modules\Item\Application\Repositories\ItemAttributeRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class GetItemAttributeService implements GetItemAttributeServiceInterface
{
    public function __construct(private readonly ItemAttributeRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'ItemAttribute not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
