<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\CycleCountLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\CycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\CycleCountLines\CreateCycleCountLineServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateCycleCountLineService implements CreateCycleCountLineServiceInterface
{
    public function __construct(private readonly CycleCountLineServiceInterface $cycleCountLineService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->cycleCountLineService->createLine($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
