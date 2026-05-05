<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Domain\Exceptions;

use RuntimeException;

class FuelLogNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Fuel log not found: {$id}");
    }
}
