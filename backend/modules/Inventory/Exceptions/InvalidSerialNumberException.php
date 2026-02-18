<?php

declare(strict_types=1);

namespace Modules\Inventory\Exceptions;

use Exception;

/**
 * Invalid Serial Number Exception
 *
 * Thrown when a serial number is in an invalid state for the requested operation.
 */
class InvalidSerialNumberException extends Exception
{
    public function __construct(string $message = "Serial number is invalid or not available", int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
