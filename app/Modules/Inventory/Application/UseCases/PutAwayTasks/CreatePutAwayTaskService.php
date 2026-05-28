<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\PutAwayTasks;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\PutAwayTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\CreatePutAwayTaskServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreatePutAwayTaskService implements CreatePutAwayTaskServiceInterface
{
    public function __construct(private readonly PutAwayTaskServiceInterface $putAwayTaskService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->putAwayTaskService->createTask($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
