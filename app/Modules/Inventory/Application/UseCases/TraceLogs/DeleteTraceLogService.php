<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\TraceLogs;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\TraceLogs\DeleteTraceLogServiceInterface;
use Modules\Inventory\Application\Repositories\TraceLogRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class DeleteTraceLogService implements DeleteTraceLogServiceInterface
{
    public function __construct(private readonly TraceLogRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'TraceLog not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}