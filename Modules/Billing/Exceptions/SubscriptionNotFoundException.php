<?php

declare(strict_types=1);

namespace Modules\Billing\Exceptions;

use Modules\Core\Exceptions\DomainException;

class SubscriptionNotFoundException extends DomainException
{
    public function __construct(int|string $subscriptionId)
    {
        parent::__construct("Subscription not found: {$subscriptionId}");
    }
}
