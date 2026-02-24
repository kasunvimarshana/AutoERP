<?php

namespace Modules\SubscriptionBilling\Domain\Entities;

class Subscription
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenantId,
        public readonly string  $planId,
        public readonly string  $subscriberType,
        public readonly string  $subscriberId,
        public readonly string  $status,
        public readonly string  $amount,
        public readonly string  $currentPeriodStart,
        public readonly string  $currentPeriodEnd,
        public readonly ?string $trialEndsAt,
        public readonly ?string $cancelledAt,
    ) {}
}
