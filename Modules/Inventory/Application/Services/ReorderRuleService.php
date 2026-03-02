<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Commands\CreateReorderRuleCommand;
use Modules\Inventory\Application\Commands\UpdateReorderRuleCommand;
use Modules\Inventory\Application\Handlers\CreateReorderRuleHandler;
use Modules\Inventory\Application\Handlers\DeleteReorderRuleHandler;
use Modules\Inventory\Application\Handlers\UpdateReorderRuleHandler;
use Modules\Inventory\Domain\Contracts\ReorderRuleRepositoryInterface;
use Modules\Inventory\Domain\Entities\ReorderRule;

class ReorderRuleService
{
    public function __construct(
        private readonly ReorderRuleRepositoryInterface $reorderRuleRepository,
        private readonly CreateReorderRuleHandler $createReorderRuleHandler,
        private readonly UpdateReorderRuleHandler $updateReorderRuleHandler,
        private readonly DeleteReorderRuleHandler $deleteReorderRuleHandler,
    ) {}

    public function listRules(
        int $tenantId,
        ?int $productId,
        ?int $warehouseId,
        bool $activeOnly,
        int $page,
        int $perPage
    ): array {
        return $this->reorderRuleRepository->findAll($tenantId, $productId, $warehouseId, $activeOnly, $page, $perPage);
    }

    public function getRule(int $tenantId, int $id): ?ReorderRule
    {
        return $this->reorderRuleRepository->findById($tenantId, $id);
    }

    public function createRule(CreateReorderRuleCommand $command): ReorderRule
    {
        return $this->createReorderRuleHandler->handle($command);
    }

    public function updateRule(UpdateReorderRuleCommand $command): ReorderRule
    {
        return $this->updateReorderRuleHandler->handle($command);
    }

    public function deleteRule(int $tenantId, int $id): void
    {
        $this->deleteReorderRuleHandler->handle($tenantId, $id);
    }

    public function listLowStockItems(int $tenantId, ?int $warehouseId, int $page, int $perPage): array
    {
        return $this->reorderRuleRepository->findLowStockItems($tenantId, $warehouseId, $page, $perPage);
    }
}
