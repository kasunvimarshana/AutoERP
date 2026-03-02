<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Commands\CreateLotCommand;
use Modules\Inventory\Application\Commands\UpdateLotCommand;
use Modules\Inventory\Application\Handlers\CreateLotHandler;
use Modules\Inventory\Application\Handlers\DeleteLotHandler;
use Modules\Inventory\Application\Handlers\UpdateLotHandler;
use Modules\Inventory\Domain\Contracts\LotRepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryLot;

class LotService
{
    public function __construct(
        private readonly LotRepositoryInterface $lotRepository,
        private readonly CreateLotHandler $createLotHandler,
        private readonly UpdateLotHandler $updateLotHandler,
        private readonly DeleteLotHandler $deleteLotHandler,
    ) {}

    public function listLots(int $tenantId, ?int $productId, ?int $warehouseId, int $page, int $perPage): array
    {
        return $this->lotRepository->findAll($tenantId, $productId, $warehouseId, $page, $perPage);
    }

    public function getLot(int $tenantId, int $id): ?InventoryLot
    {
        return $this->lotRepository->findById($tenantId, $id);
    }

    public function createLot(CreateLotCommand $command): InventoryLot
    {
        return $this->createLotHandler->handle($command);
    }

    public function updateLot(UpdateLotCommand $command): InventoryLot
    {
        return $this->updateLotHandler->handle($command);
    }

    public function deleteLot(int $tenantId, int $id): void
    {
        $this->deleteLotHandler->handle($tenantId, $id);
    }
}
