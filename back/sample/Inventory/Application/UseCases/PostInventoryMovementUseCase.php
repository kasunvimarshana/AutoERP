<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases;

use Modules\Inventory\Application\DTOs\PostInventoryMovementDTO;
use Modules\Inventory\Domain\Aggregates\InventoryMovementAggregate;
use Modules\Inventory\Domain\Services\InventoryEngineService;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovement;

class PostInventoryMovementUseCase
{
    public function __construct(
        private readonly InventoryEngineService $inventoryEngineService,
    ) {
    }

    public function execute(PostInventoryMovementDTO $dto): StockMovement
    {
        $aggregate = InventoryMovementAggregate::fromPostDTO($dto);

        return $this->inventoryEngineService->post($aggregate->line());
    }
}
