<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\PickingTasks;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\PickingTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\UpdatePickingTaskServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdatePickingTaskService implements UpdatePickingTaskServiceInterface
{
    public function __construct(private readonly PickingTaskServiceInterface $pickingTaskService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->pickingTaskService->updateTask($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
