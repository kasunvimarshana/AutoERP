<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Wms\Application\Commands\BeginCycleCountProgressCommand;
use Modules\Wms\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Wms\Domain\Entities\CycleCount;
use Modules\Wms\Domain\Enums\CycleCountStatus;

class BeginCycleCountProgressHandler extends BaseHandler
{
    public function __construct(
        private readonly CycleCountRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(BeginCycleCountProgressCommand $command): CycleCount
    {
        return $this->transaction(function () use ($command): CycleCount {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (BeginCycleCountProgressCommand $cmd): CycleCount {
                    $existing = $this->repository->findById($cmd->id, $cmd->tenantId);

                    if ($existing === null) {
                        throw new \DomainException("Cycle count with ID '{$cmd->id}' not found.");
                    }

                    if ($existing->status !== CycleCountStatus::Draft->value) {
                        throw new \DomainException(
                            "Cycle count must be in 'draft' status to begin progress. Current status: '{$existing->status}'."
                        );
                    }

                    return $this->repository->save(new CycleCount(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        warehouseId: $existing->warehouseId,
                        status: CycleCountStatus::InProgress->value,
                        notes: $existing->notes,
                        startedAt: now()->toIso8601String(),
                        completedAt: $existing->completedAt,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    ));
                });
        });
    }
}
