<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Services;

use Modules\Wms\Application\Commands\CreateBinCommand;
use Modules\Wms\Application\Commands\DeleteBinCommand;
use Modules\Wms\Application\Commands\UpdateBinCommand;
use Modules\Wms\Application\Handlers\CreateBinHandler;
use Modules\Wms\Application\Handlers\DeleteBinHandler;
use Modules\Wms\Application\Handlers\UpdateBinHandler;
use Modules\Wms\Domain\Contracts\BinRepositoryInterface;
use Modules\Wms\Domain\Entities\Bin;

class BinService
{
    public function __construct(
        private readonly BinRepositoryInterface $repository,
        private readonly CreateBinHandler $createHandler,
        private readonly UpdateBinHandler $updateHandler,
        private readonly DeleteBinHandler $deleteHandler,
    ) {}

    public function createBin(CreateBinCommand $cmd): Bin
    {
        return $this->createHandler->handle($cmd);
    }

    public function updateBin(UpdateBinCommand $cmd): Bin
    {
        return $this->updateHandler->handle($cmd);
    }

    public function deleteBin(DeleteBinCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }

    public function findById(int $id, int $tenantId): ?Bin
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findByAisle(int $tenantId, int $aisleId): array
    {
        return $this->repository->findByAisle($tenantId, $aisleId);
    }
}
