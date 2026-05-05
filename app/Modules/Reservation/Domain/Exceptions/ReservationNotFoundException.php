<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Exceptions;

use RuntimeException;

class ReservationNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Reservation [{$id}] not found.");
    }
}
