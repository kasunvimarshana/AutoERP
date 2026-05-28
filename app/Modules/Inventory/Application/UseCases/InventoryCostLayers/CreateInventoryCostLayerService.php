<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryCostLayers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\InventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\CreateInventoryCostLayerServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateInventoryCostLayerService implements CreateInventoryCostLayerServiceInterface
{
    public function __construct(private readonly InventoryCostLayerServiceInterface $inventoryCostLayerService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->inventoryCostLayerService->createLayer($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
