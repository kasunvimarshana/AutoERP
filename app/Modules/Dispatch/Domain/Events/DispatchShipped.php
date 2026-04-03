<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class DispatchShipped extends BaseEvent
{
    public function __construct(
        public readonly int $dispatchId,
        public readonly int $shippedBy,
    ) {
        parent::__construct($shippedBy);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'         => $this->dispatchId,
            'shipped_by' => $this->shippedBy,
        ]);
    }
}
