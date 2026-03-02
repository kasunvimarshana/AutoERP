<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Inventory\Domain\Contracts\ReorderRuleRepositoryInterface;

class DeleteReorderRuleHandler extends BaseHandler
{
    public function __construct(
        private readonly ReorderRuleRepositoryInterface $reorderRuleRepository,
    ) {}

    public function handle(int $tenantId, int $id): void
    {
        $rule = $this->reorderRuleRepository->findById($tenantId, $id);

        if ($rule === null) {
            throw new \DomainException("Reorder rule [{$id}] not found.");
        }

        $this->reorderRuleRepository->delete($tenantId, $id);
    }
}
