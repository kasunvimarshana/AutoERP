<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Tenant\Domain\Entities\TenantPlan;

class TenantPlanCreated extends BaseEvent
{
    public function __construct(public readonly TenantPlan $plan)
    {
        parent::__construct(0);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->plan->getId(),
            'name' => $this->plan->getName(),
            'slug' => $this->plan->getSlug(),
            'is_active' => $this->plan->isActive(),
        ]);
    }
}
