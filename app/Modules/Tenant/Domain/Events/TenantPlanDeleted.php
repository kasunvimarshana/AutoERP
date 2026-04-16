<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class TenantPlanDeleted extends BaseEvent
{
    public function __construct(private readonly int $planId)
    {
        parent::__construct(0);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->planId,
        ]);
    }
}
