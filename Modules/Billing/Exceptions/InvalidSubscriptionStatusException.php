<?php

declare(strict_types=1);

namespace Modules\Billing\Exceptions;

use Modules\Core\Exceptions\ValidationException;

class InvalidSubscriptionStatusException extends ValidationException
{
    public function __construct(string $currentStatus, string $action)
    {
        parent::__construct("Cannot {$action} subscription in status: {$currentStatus}");
    }
}
