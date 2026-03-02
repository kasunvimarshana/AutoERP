<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Commands;

final readonly class UpdateReorderRuleCommand
{
    public function __construct(
        public int $tenantId,
        public int $id,
        public string $reorderPoint,
        public string $reorderQuantity,
        public bool $isActive,
    ) {}
}
