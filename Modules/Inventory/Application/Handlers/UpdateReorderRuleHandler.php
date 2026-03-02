<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\UpdateReorderRuleCommand;
use Modules\Inventory\Domain\Contracts\ReorderRuleRepositoryInterface;
use Modules\Inventory\Domain\Entities\ReorderRule;

class UpdateReorderRuleHandler extends BaseHandler
{
    public function __construct(
        private readonly ReorderRuleRepositoryInterface $reorderRuleRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateReorderRuleCommand $command): ReorderRule
    {
        return $this->transaction(function () use ($command): ReorderRule {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateReorderRuleCommand $cmd): ReorderRule {
                    $existing = $this->reorderRuleRepository->findById($cmd->tenantId, $cmd->id);

                    if ($existing === null) {
                        throw new \DomainException("Reorder rule [{$cmd->id}] not found.");
                    }

                    return $this->reorderRuleRepository->save(new ReorderRule(
                        id: $existing->id,
                        tenantId: $existing->tenantId,
                        productId: $existing->productId,
                        warehouseId: $existing->warehouseId,
                        reorderPoint: $cmd->reorderPoint,
                        reorderQuantity: $cmd->reorderQuantity,
                        isActive: $cmd->isActive,
                        createdAt: $existing->createdAt,
                        updatedAt: null,
                    ));
                });
        });
    }
}
