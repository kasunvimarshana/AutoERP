<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ItemAttributeGroups;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\CreateItemAttributeGroupServiceInterface;
use Modules\Item\Application\Repositories\ItemAttributeGroupRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class CreateItemAttributeGroupService implements CreateItemAttributeGroupServiceInterface
{
    public function __construct(private readonly ItemAttributeGroupRepositoryInterface $repository)
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
