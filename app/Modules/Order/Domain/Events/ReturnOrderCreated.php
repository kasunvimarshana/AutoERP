<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ReturnOrderCreated extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly string $returnId,
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), ['returnId' => $this->returnId]);
    }
}
