<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Commands;

final readonly class ConfirmPurchaseOrderCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
