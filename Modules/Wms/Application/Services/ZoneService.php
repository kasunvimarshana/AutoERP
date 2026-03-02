<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Services;

use Modules\Wms\Application\Commands\CreateZoneCommand;
use Modules\Wms\Application\Commands\DeleteZoneCommand;
use Modules\Wms\Application\Commands\UpdateZoneCommand;
use Modules\Wms\Application\Handlers\CreateZoneHandler;
use Modules\Wms\Application\Handlers\DeleteZoneHandler;
use Modules\Wms\Application\Handlers\UpdateZoneHandler;
use Modules\Wms\Domain\Contracts\ZoneRepositoryInterface;
use Modules\Wms\Domain\Entities\Zone;

class ZoneService
{
    public function __construct(
        private readonly ZoneRepositoryInterface $repository,
        private readonly CreateZoneHandler $createHandler,
        private readonly UpdateZoneHandler $updateHandler,
        private readonly DeleteZoneHandler $deleteHandler,
    ) {}

    public function createZone(CreateZoneCommand $cmd): Zone
    {
        return $this->createHandler->handle($cmd);
    }

    public function updateZone(UpdateZoneCommand $cmd): Zone
    {
        return $this->updateHandler->handle($cmd);
    }

    public function deleteZone(DeleteZoneCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findById(int $id, int $tenantId): ?Zone
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findAll(int $tenantId, int $warehouseId, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $warehouseId, $page, $perPage);
    }

    public function findByWarehouse(int $tenantId, int $warehouseId): array
    {
        return $this->repository->findByWarehouse($tenantId, $warehouseId);
    }
}
