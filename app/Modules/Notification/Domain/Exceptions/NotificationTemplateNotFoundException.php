<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Exceptions;

use RuntimeException;

class NotificationTemplateNotFoundException extends RuntimeException
{
    public function __construct(int|string $id)
    {
        parent::__construct("NotificationTemplate [{$id}] not found.", 404);
    }
}
