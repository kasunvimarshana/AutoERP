<?php

namespace Modules\HR\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class PerformanceGoalCreated extends DomainEvent
{
    public function __construct(public readonly string $goalId)
    {
        parent::__construct();
    }
}
