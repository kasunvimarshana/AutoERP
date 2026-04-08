<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class DepartmentDeleted extends BaseEvent
{
    public function __construct(public readonly int $departmentId, int $tenantId)
    {
        parent::__construct($tenantId, $departmentId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->departmentId,
        ]);
    }
}
