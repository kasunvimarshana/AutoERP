<?php

declare(strict_types=1);

namespace Modules\Inventory\Exceptions;

use Exception;

/**
 * Duplicate Serial Number Exception
 *
 * Thrown when attempting to register a serial number that already exists.
 */
class DuplicateSerialNumberException extends Exception
{
    public function __construct(string $message = "Serial number already exists", int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
