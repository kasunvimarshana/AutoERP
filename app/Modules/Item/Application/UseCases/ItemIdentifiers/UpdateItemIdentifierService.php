<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemIdentifiers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemIdentifiers\UpdateItemIdentifierServiceInterface;
use Modules\Item\Application\Repositories\ItemIdentifierRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class UpdateItemIdentifierService implements UpdateItemIdentifierServiceInterface
{
    public function __construct(private readonly ItemIdentifierRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'ItemIdentifier not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
