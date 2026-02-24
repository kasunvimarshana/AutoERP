<?php

namespace Modules\SubscriptionBilling\Domain\Events;

use Modules\Shared\Domain\Events\DomainEvent;

class SubscriptionRenewed extends DomainEvent
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $tenantId           = '',
        public readonly string $subscriberId       = '',
        public readonly string $planName           = '',
        public readonly string $amount             = '0',
        public readonly string $currency           = 'USD',
        public readonly string $currentPeriodStart = '',
        public readonly string $currentPeriodEnd   = '',
    ) {
        parent::__construct();
    }
}
