<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\CycleCountLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\CycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\UpdateCycleCountLineServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateCycleCountLineService implements UpdateCycleCountLineServiceInterface
{
    public function __construct(private readonly CycleCountLineServiceInterface $cycleCountLineService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->cycleCountLineService->updateLine($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
