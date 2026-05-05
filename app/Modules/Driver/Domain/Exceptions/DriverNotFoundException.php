<?php

declare(strict_types=1);

namespace Modules\Driver\Domain\Exceptions;

use RuntimeException;

class DriverNotFoundException extends RuntimeException
{
    public function __construct(int|string $driverId)
    {
        parent::__construct("Driver [{$driverId}] not found.");
    }
}
