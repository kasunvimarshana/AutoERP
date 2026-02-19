<?php

declare(strict_types=1);

namespace Modules\Document\Exceptions;

use Exception;

class InsufficientPermissionException extends Exception
{
    public function __construct(string $message = 'Insufficient permissions for this operation')
    {
        parent::__construct($message, 403);
    }
}
