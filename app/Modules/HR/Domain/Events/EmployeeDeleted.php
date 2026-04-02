<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class EmployeeDeleted extends BaseEvent
{
    public function __construct(public readonly int $employeeId, int $tenantId)
    {
        parent::__construct($tenantId, $employeeId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->employeeId,
        ]);
    }
}
