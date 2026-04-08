<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class LeaveRequestDeleted extends BaseEvent
{
    public function __construct(public readonly int $leaveRequestId, int $tenantId)
    {
        parent::__construct($tenantId, $leaveRequestId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->leaveRequestId,
        ]);
    }
}
