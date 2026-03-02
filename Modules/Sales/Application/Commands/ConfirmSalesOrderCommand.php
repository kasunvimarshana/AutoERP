<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Commands;

final readonly class ConfirmSalesOrderCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
