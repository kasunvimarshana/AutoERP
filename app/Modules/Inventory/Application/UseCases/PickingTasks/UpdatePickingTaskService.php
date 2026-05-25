<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\PickingTasks;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\UpdatePickingTaskServiceInterface;
use Modules\Inventory\Application\Repositories\PickingTaskRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdatePickingTaskService implements UpdatePickingTaskServiceInterface
{
    public function __construct(private readonly PickingTaskRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'PickingTask not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}