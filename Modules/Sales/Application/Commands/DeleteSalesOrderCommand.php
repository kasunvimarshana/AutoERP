<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Commands;

final readonly class DeleteSalesOrderCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
