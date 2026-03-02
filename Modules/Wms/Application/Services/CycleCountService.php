<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Services;

use Modules\Wms\Application\Commands\BeginCycleCountProgressCommand;
use Modules\Wms\Application\Commands\CompleteCycleCountCommand;
use Modules\Wms\Application\Commands\DeleteCycleCountCommand;
use Modules\Wms\Application\Commands\RecordCycleCountLineCommand;
use Modules\Wms\Application\Commands\StartCycleCountCommand;
use Modules\Wms\Application\Handlers\BeginCycleCountProgressHandler;
use Modules\Wms\Application\Handlers\CompleteCycleCountHandler;
use Modules\Wms\Application\Handlers\DeleteCycleCountHandler;
use Modules\Wms\Application\Handlers\RecordCycleCountLineHandler;
use Modules\Wms\Application\Handlers\StartCycleCountHandler;
use Modules\Wms\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Wms\Domain\Entities\CycleCount;
use Modules\Wms\Domain\Entities\CycleCountLine;

class CycleCountService
{
    public function __construct(
        private readonly CycleCountRepositoryInterface $repository,
        private readonly StartCycleCountHandler $startHandler,
        private readonly BeginCycleCountProgressHandler $beginProgressHandler,
        private readonly RecordCycleCountLineHandler $recordLineHandler,
        private readonly CompleteCycleCountHandler $completeHandler,
        private readonly DeleteCycleCountHandler $deleteHandler,
    ) {}

    public function startCycleCount(StartCycleCountCommand $cmd): CycleCount
    {
        return $this->startHandler->handle($cmd);
    }

    public function beginProgress(BeginCycleCountProgressCommand $cmd): CycleCount
    {
        return $this->beginProgressHandler->handle($cmd);
    }

    public function recordLine(RecordCycleCountLineCommand $cmd): CycleCountLine
    {
        return $this->recordLineHandler->handle($cmd);
    }

    public function completeCycleCount(CompleteCycleCountCommand $cmd): CycleCount
    {
        return $this->completeHandler->handle($cmd);
    }

    public function findById(int $id, int $tenantId): ?CycleCount
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function findAll(int $tenantId, int $warehouseId, int $page, int $perPage): array
    {
        return $this->repository->findAll($tenantId, $warehouseId, $page, $perPage);
    }

    public function findLines(int $cycleCountId, int $tenantId): array
    {
        return $this->repository->findLines($cycleCountId, $tenantId);
    }

    public function deleteCycleCount(DeleteCycleCountCommand $cmd): void
    {
        $this->deleteHandler->handle($cmd);
    }
}
