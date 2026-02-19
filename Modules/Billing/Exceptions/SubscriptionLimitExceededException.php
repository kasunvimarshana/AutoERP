<?php

declare(strict_types=1);

namespace Modules\Billing\Exceptions;

use Modules\Core\Exceptions\ValidationException;

class SubscriptionLimitExceededException extends ValidationException
{
    public function __construct(string $limitType, int $limit, int $current)
    {
        parent::__construct("Subscription limit exceeded for {$limitType}: limit={$limit}, current={$current}");
    }
}
