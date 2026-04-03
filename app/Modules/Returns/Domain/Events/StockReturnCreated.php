<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Returns\Domain\Entities\StockReturn;

class StockReturnCreated extends BaseEvent
{
    public function __construct(public readonly StockReturn $stockReturn)
    {
        parent::__construct($stockReturn->getTenantId(), $stockReturn->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->stockReturn->getId(),
            'tenant_id' => $this->stockReturn->getTenantId(),
        ]);
    }
}
