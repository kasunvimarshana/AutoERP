<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Events;

use Modules\Returns\Domain\Entities\SalesReturn;

class SalesReturnCreated
{
    public function __construct(public readonly SalesReturn $salesReturn) {}
}
