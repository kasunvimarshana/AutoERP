<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\CycleCountHeaders;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\CreateCycleCountHeaderServiceInterface;
use Modules\Inventory\Application\Repositories\CycleCountHeaderRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateCycleCountHeaderService implements CreateCycleCountHeaderServiceInterface
{
    public function __construct(private readonly CycleCountHeaderRepositoryInterface $repository)
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
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}