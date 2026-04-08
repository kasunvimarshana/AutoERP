<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Events;

use Modules\Returns\Domain\Entities\PurchaseReturn;

class PurchaseReturnCreated
{
    public function __construct(public readonly PurchaseReturn $purchaseReturn) {}
}
