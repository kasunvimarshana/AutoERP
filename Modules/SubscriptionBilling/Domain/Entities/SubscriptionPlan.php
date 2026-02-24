<?php

namespace Modules\SubscriptionBilling\Domain\Entities;

class SubscriptionPlan
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenantId,
        public readonly string  $name,
        public readonly string  $code,
        public readonly string  $billingCycle,
        public readonly string  $price,
        public readonly int     $trialDays,
        public readonly bool    $isActive,
        public readonly ?string $description,
    ) {}
}
