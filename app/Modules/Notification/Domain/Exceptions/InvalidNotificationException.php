<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Exceptions;

use RuntimeException;

class InvalidNotificationException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
