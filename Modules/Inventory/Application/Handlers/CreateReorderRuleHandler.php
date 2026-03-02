<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;
use Modules\Inventory\Application\Commands\CreateReorderRuleCommand;
use Modules\Inventory\Domain\Contracts\ReorderRuleRepositoryInterface;
use Modules\Inventory\Domain\Entities\ReorderRule;

class CreateReorderRuleHandler extends BaseHandler
{
    public function __construct(
        private readonly ReorderRuleRepositoryInterface $reorderRuleRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateReorderRuleCommand $command): ReorderRule
    {
        return $this->transaction(function () use ($command): ReorderRule {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateReorderRuleCommand $cmd): ReorderRule {
                    if ($this->reorderRuleRepository->existsForProductAndWarehouse(
                        $cmd->tenantId,
                        $cmd->productId,
                        $cmd->warehouseId,
                    )) {
                        throw new \DomainException(
                            'A reorder rule already exists for this product and warehouse.'
                        );
                    }

                    return $this->reorderRuleRepository->save(new ReorderRule(
                        id: null,
                        tenantId: $cmd->tenantId,
                        productId: $cmd->productId,
                        warehouseId: $cmd->warehouseId,
                        reorderPoint: $cmd->reorderPoint,
                        reorderQuantity: $cmd->reorderQuantity,
                        isActive: $cmd->isActive,
                        createdAt: null,
                        updatedAt: null,
                    ));
                });
        });
    }
}
