<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Events;

use Modules\Order\Domain\Entities\SalesOrder;

class SalesOrderCreated
{
    public function __construct(
        public readonly SalesOrder $salesOrder,
    ) {}
}
