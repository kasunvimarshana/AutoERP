<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Wms\Application\Commands\RecordCycleCountLineCommand;
use Modules\Wms\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Wms\Domain\Entities\CycleCountLine;
use Modules\Wms\Domain\Enums\CycleCountStatus;

class RecordCycleCountLineHandler extends BaseHandler
{
    public function __construct(
        private readonly CycleCountRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(RecordCycleCountLineCommand $command): CycleCountLine
    {
        return $this->transaction(function () use ($command): CycleCountLine {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (RecordCycleCountLineCommand $cmd): CycleCountLine {
                    $cycleCount = $this->repository->findById($cmd->cycleCountId, $cmd->tenantId);

                    if ($cycleCount === null) {
                        throw new \DomainException("Cycle count with ID '{$cmd->cycleCountId}' not found.");
                    }

                    if ($cycleCount->status !== CycleCountStatus::InProgress->value) {
                        throw new \DomainException(
                            "Cycle count must be 'in_progress' to record lines. Current status: '{$cycleCount->status}'."
                        );
                    }

                    $variance = bcsub((string) $cmd->countedQty, (string) $cmd->systemQty, 4);

                    $lines = $this->repository->saveLines($cmd->cycleCountId, $cmd->tenantId, [[
                        'product_id' => $cmd->productId,
                        'bin_id' => $cmd->binId,
                        'system_qty' => $cmd->systemQty,
                        'counted_qty' => $cmd->countedQty,
                        'variance' => $variance,
                        'notes' => $cmd->notes,
                    ]]);

                    return $lines[0];
                });
        });
    }
}
