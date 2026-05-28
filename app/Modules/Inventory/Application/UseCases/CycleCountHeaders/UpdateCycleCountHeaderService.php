<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\CycleCountHeaders;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\CycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders\UpdateCycleCountHeaderServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateCycleCountHeaderService implements UpdateCycleCountHeaderServiceInterface
{
    public function __construct(private readonly CycleCountServiceInterface $cycleCountService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->cycleCountService->updateCount($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
