<?php

declare(strict_types=1);

namespace Modules\Billing\Exceptions;

use Modules\Core\Exceptions\DomainException;

class PlanNotFoundException extends DomainException
{
    public function __construct(int|string $planId)
    {
        parent::__construct("Plan not found: {$planId}");
    }
}
