<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Returns\Domain\Entities\StockReturnLine;

class StockReturnLinePassed extends BaseEvent
{
    public function __construct(public readonly StockReturnLine $line)
    {
        parent::__construct($line->getTenantId(), $line->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->line->getId(),
            'tenant_id' => $this->line->getTenantId(),
        ]);
    }
}
