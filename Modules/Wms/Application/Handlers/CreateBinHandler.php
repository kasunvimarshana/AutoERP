<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Wms\Application\Commands\CreateBinCommand;
use Modules\Wms\Domain\Contracts\BinRepositoryInterface;
use Modules\Wms\Domain\Entities\Bin;

class CreateBinHandler extends BaseHandler
{
    public function __construct(
        private readonly BinRepositoryInterface $repository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateBinCommand $command): Bin
    {
        return $this->transaction(function () use ($command): Bin {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateBinCommand $cmd): Bin {
                    $existing = $this->repository->findByAisle($cmd->tenantId, $cmd->aisleId);
                    foreach ($existing as $bin) {
                        if (strtolower($bin->code) === strtolower($cmd->code)) {
                            throw new \DomainException(
                                "A bin with code '{$cmd->code}' already exists in this aisle."
                            );
                        }
                    }

                    return $this->repository->save(new Bin(
                        id: null,
                        tenantId: $cmd->tenantId,
                        aisleId: $cmd->aisleId,
                        code: $cmd->code,
                        description: $cmd->description,
                        maxCapacity: $cmd->maxCapacity,
                        currentCapacity: 0,
                        isActive: true,
                        createdAt: null,
                        updatedAt: null,
                    ));
                });
        });
    }
}
