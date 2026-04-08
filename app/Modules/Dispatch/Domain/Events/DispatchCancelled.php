<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class DispatchCancelled extends BaseEvent
{
    public function __construct(
        public readonly int $dispatchId,
        int $tenantId,
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->dispatchId,
            'tenant_id' => $this->tenantId,
        ]);
    }
}
