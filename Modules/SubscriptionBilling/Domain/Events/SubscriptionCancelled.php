<?php

namespace Modules\SubscriptionBilling\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class SubscriptionCancelled extends DomainEvent
{
    public function __construct(public readonly string $subscriptionId)
    {
        parent::__construct();
    }
}
