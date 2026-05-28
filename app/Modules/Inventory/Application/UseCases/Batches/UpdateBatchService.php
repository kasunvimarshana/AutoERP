<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\Batches;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\BatchServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Batches\UpdateBatchServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateBatchService implements UpdateBatchServiceInterface
{
    public function __construct(private readonly BatchServiceInterface $batchService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->batchService->updateBatch($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
