<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\TraceLogs;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\TraceLogServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\CreateTraceLogServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateTraceLogService implements CreateTraceLogServiceInterface
{
    public function __construct(private readonly TraceLogServiceInterface $traceLogService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->traceLogService->createTraceLog($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
