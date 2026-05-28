<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryCostLayers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\InventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\UpdateInventoryCostLayerServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateInventoryCostLayerService implements UpdateInventoryCostLayerServiceInterface
{
    public function __construct(private readonly InventoryCostLayerServiceInterface $inventoryCostLayerService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->inventoryCostLayerService->updateLayer($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
