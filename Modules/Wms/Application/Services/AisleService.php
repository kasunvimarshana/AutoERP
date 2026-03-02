<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Services;

use Modules\Wms\Application\Commands\CreateAisleCommand;
use Modules\Wms\Application\Commands\DeleteAisleCommand;
use Modules\Wms\Application\Commands\UpdateAisleCommand;
use Modules\Wms\Application\Handlers\CreateAisleHandler;
use Modules\Wms\Application\Handlers\DeleteAisleHandler;
use Modules\Wms\Application\Handlers\UpdateAisleHandler;
use Modules\Wms\Domain\Contracts\AisleRepositoryInterface;
use Modules\Wms\Domain\Entities\Aisle;

class AisleService
{
    public function __construct(
        private readonly AisleRepositoryInterface $repository,
        private readonly CreateAisleHandler $createHandler,
        private readonly UpdateAisleHandler $updateHandler,
        private readonly DeleteAisleHandler $deleteHandler,
    ) {}

    public function createAisle(CreateAisleCommand $cmd): Aisle
    {
        return $this->createHandler->handle($cmd);
    }

    public function updateAisle(UpdateAisleCommand $cmd): Aisle
    {
        return $this->updateHandler->handle($cmd);
    }

    public function deleteAisle(DeleteAisleCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findById(int $id, int $tenantId): ?Aisle
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findByZone(int $tenantId, int $zoneId): array
    {
        return $this->repository->findByZone($tenantId, $zoneId);
    }
}
