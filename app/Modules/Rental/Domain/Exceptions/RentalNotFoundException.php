<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Exceptions;

use RuntimeException;

class RentalNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Rental with id [{$id}] not found.");
    }
}
