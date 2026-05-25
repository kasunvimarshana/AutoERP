<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemAttributes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\DeleteItemAttributeServiceInterface;
use Modules\Item\Application\Repositories\ItemAttributeRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class DeleteItemAttributeService implements DeleteItemAttributeServiceInterface
{
    public function __construct(private readonly ItemAttributeRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'ItemAttribute not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
