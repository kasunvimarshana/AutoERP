<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Wms\Application\Commands\StartCycleCountCommand;
use Modules\Wms\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Wms\Domain\Entities\CycleCount;
use Modules\Wms\Domain\Enums\CycleCountStatus;

class StartCycleCountHandler extends BaseHandler
{
    public function __construct(
        private readonly CycleCountRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(StartCycleCountCommand $command): CycleCount
    {
        return $this->transaction(function () use ($command): CycleCount {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (StartCycleCountCommand $cmd): CycleCount {
                    return $this->repository->save(new CycleCount(
                        id: null,
                        tenantId: $cmd->tenantId,
                        warehouseId: $cmd->warehouseId,
                        status: CycleCountStatus::Draft->value,
                        notes: $cmd->notes,
                        startedAt: null,
                        completedAt: null,
                        createdAt: null,
                        updatedAt: null,
                    ));
                });
        });
    }
}
