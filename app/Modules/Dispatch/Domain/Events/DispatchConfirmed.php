<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class DispatchConfirmed extends BaseEvent
{
    public function __construct(
        public readonly int $dispatchId,
        public readonly int $confirmedBy,
    ) {
        parent::__construct($confirmedBy);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'           => $this->dispatchId,
            'confirmed_by' => $this->confirmedBy,
        ]);
    }
}
