<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class DispatchLineDeleted extends BaseEvent
{
    public function __construct(
        public readonly int $lineId,
        public readonly int $dispatchId,
    ) {
        parent::__construct($dispatchId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'          => $this->lineId,
            'dispatch_id' => $this->dispatchId,
        ]);
    }
}
