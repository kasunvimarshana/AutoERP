<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class PositionDeleted extends BaseEvent
{
    public function __construct(public readonly int $positionId, int $tenantId)
    {
        parent::__construct($tenantId, $positionId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->positionId,
        ]);
    }
}
