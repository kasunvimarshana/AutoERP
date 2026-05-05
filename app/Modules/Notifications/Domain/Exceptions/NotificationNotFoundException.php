<?php

declare(strict_types=1);

namespace Modules\Notifications\Domain\Exceptions;

use RuntimeException;

class NotificationNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Notification [{$id}] not found.");
    }
}
